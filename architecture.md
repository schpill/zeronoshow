# Architecture — ZeroNoShow

**Smart No-Show Prevention via SMS Reliability Scoring**

Version: 1.0
Date: 2026-03-12
Based on: PRD v2.0

---

## 1. Overview

### Architecture Style
**Modular Monolith** [DECIDED — PRD explicit]

Single Laravel 12 application serving three responsibilities:
1. REST API consumed by the Vue.js SPA
2. Background queue workers (SMS sending, score recalculation)
3. Scheduler (reminder triggers, auto-cancellations)

This is the right choice for the MVP scale (≤ 100 paying businesses at 12 months) and team size (solo/small). No distributed system complexity, no inter-service networking, full ACID transactions across all operations.

### Guiding Principles

1. **Boring tech wins** — Laravel + PostgreSQL + Redis is a proven, well-documented stack. No exotic choices.
2. **Queue everything async** — No SMS send is ever synchronous. All external calls go through the queue.
3. **Fail loudly, never silently** — Every SMS failure is logged with detail. No black holes.
4. **Design for replaceability** — SmsService abstracts Twilio. If Twilio raises prices, swapping is one class change.
5. **Scale when needed, not before** — Vertical scaling on a single DigitalOcean droplet is sufficient for Year 1.

### Scale Target

| Dimension | MVP | Year 1 |
|---|---|---|
| Paying businesses | 20 | 100 |
| Reservations/day (total) | ~200 | ~1,000 |
| SMS/day | ~400 | ~2,000 |
| Concurrent dashboard users | < 50 | < 200 |
| Database size | < 1 GB | < 5 GB |

This is a **small-scale system**. No sharding, no multi-region, no CDN for API needed at this stage.

### Key Constraints

- Solo or very small team — no ops complexity
- MVP in 4–6 weeks
- French market first (Europe/Paris timezone, GDPR scope)
- 19€/month pricing — infrastructure cost must stay well below 100€/month

---

## 2. System Context Diagram (C4 Level 1)

```
                    ┌─────────────────┐
                    │  Business Owner │
                    │  (Marc, Sophie, │
                    │  Dr. Lefebvre)  │
                    └────────┬────────┘
                             │ HTTPS / Browser
                             ▼
              ┌──────────────────────────────┐
              │          ZeroNoShow          │
              │                              │
              │  SMS-based no-show           │
              │  prevention platform         │
              │                              │
              └──────┬──────────┬────────────┘
                     │          │
          SMS API    │          │  Billing API
                     ▼          ▼
               ┌──────────┐  ┌──────────┐
               │  Twilio  │  │  Stripe  │
               └──────────┘  └──────────┘
                     │
               SMS to client
                     │
                     ▼
          ┌──────────────────────┐
          │  Client (passive)    │
          │  Receives SMS        │
          │  Clicks confirm link │
          │  No account needed   │
          └──────────────────────┘
                     │
              HTTPS (confirmation page)
                     │
                     ▼
              ┌──────────────────────────────┐
              │          ZeroNoShow          │
              │  (public confirmation page)  │
              └──────────────────────────────┘
```

---

## 3. Container Diagram (C4 Level 2)

```
┌─────────────────────────────────────────────────────────────────┐
│                         ZeroNoShow                              │
│                                                                 │
│  ┌──────────────────┐        ┌──────────────────────────────┐  │
│  │   Vue.js 3 SPA   │──────▶│      Laravel 12 API          │  │
│  │   (Vite/TW CSS)  │ HTTPS  │  (PHP, Sanctum auth)         │  │
│  │   Browser-side   │        │  Routes: /api/*              │  │
│  └──────────────────┘        │  Routes: /c/{token} (public) │  │
│                              └──────────┬───────────────────┘  │
│                                         │                       │
│                         ┌───────────────┼────────────────┐      │
│                         │               │                │      │
│                         ▼               ▼                ▼      │
│                  ┌────────────┐  ┌────────────┐  ┌──────────┐  │
│                  │ PostgreSQL │  │   Redis    │  │  Stripe  │  │
│                  │    16      │  │  (Queue +  │  │ Webhook  │  │
│                  │  Primary   │  │   Cache)   │  │ handler  │  │
│                  │    DB      │  │            │  └──────────┘  │
│                  └────────────┘  └─────┬──────┘               │
│                                        │                       │
│                              ┌─────────┴──────────┐           │
│                              │                    │           │
│                              ▼                    ▼           │
│                    ┌──────────────────┐  ┌──────────────────┐ │
│                    │  Queue Worker    │  │    Scheduler     │ │
│                    │  (Laravel)       │  │  (Laravel cron   │ │
│                    │  SMS jobs        │  │   every minute)  │ │
│                    │  Score jobs      │  │  Reminder checks │ │
│                    └────────┬─────────┘  │  Auto-cancel     │ │
│                             │            └──────────────────┘ │
└─────────────────────────────┼───────────────────────────────────┘
                              │ SMS API calls
                              ▼
                        ┌──────────┐
                        │  Twilio  │
                        │  (SMS)   │
                        └──────────┘
                              │ Webhooks (delivery status)
                              └──────────────────────────▶ Laravel API
                                                          POST /webhooks/twilio
```

---

## 4. Component Breakdown

### 4.1 Laravel API

- **Responsibility:** Handles all HTTP requests — authentication, reservation CRUD, client confirmation, webhooks, dashboard data.
- **Technology:** Laravel 12, PHP 8.3+
- **Interfaces:** REST JSON API for SPA; HTML response for `/c/{token}` confirmation page
- **Scaling strategy:** Stateless — can be horizontally scaled behind a load balancer if needed (not needed for MVP)
- **Key dependencies:** PostgreSQL, Redis, SmsService, StripeService

### 4.2 Vue.js 3 SPA

- **Responsibility:** Business-facing dashboard — reservation creation, status views, billing management.
- **Technology:** Vue.js 3 (Composition API), Vite, Tailwind CSS 3
- **Interfaces:** Consumes Laravel REST API via Axios; uses Sanctum cookie-based auth
- **Scaling strategy:** Static assets served by Nginx; CDN optional at this scale
- **Key dependencies:** Laravel API

### 4.3 Queue Worker

- **Responsibility:** Processes all async jobs — SMS sending (verification, reminders), reliability score recalculation, email notifications.
- **Technology:** Laravel Queue, Redis driver
- **Interfaces:** Reads from Redis queue; writes to PostgreSQL; calls Twilio API
- **Scaling strategy:** Single worker process for MVP; add workers if queue depth grows
- **Key dependencies:** Redis, PostgreSQL, Twilio

### 4.4 Scheduler

- **Responsibility:** Triggers time-based operations every minute — checks for reservations needing 2h reminder, 30min reminder, or auto-cancellation.
- **Technology:** Laravel Scheduler (cron `* * * * * php artisan schedule:run`)
- **Interfaces:** Dispatches jobs to Redis queue; writes directly to PostgreSQL for auto-cancellations
- **Scaling strategy:** Single process; must run on exactly one server (cron on the app server)
- **Key dependencies:** PostgreSQL, Redis

### 4.5 SmsService

- **Responsibility:** Abstracts all Twilio interactions — send SMS, validate webhooks, record delivery status.
- **Technology:** `twilio/sdk` PHP library
- **Interfaces:** Called by queue jobs; receives webhooks via `TwilioWebhookController`
- **Key design:** Interface-based — `SmsServiceInterface` implemented by `TwilioSmsService`. Swap without touching callers.

### 4.6 ReliabilityScoreService

- **Responsibility:** Calculates and updates the cross-business reliability score for a phone number.
- **Technology:** Pure PHP service class, no external dependencies
- **Formula:** `score = shows_count / (shows_count + no_shows_count) * 100`
- **Interfaces:** Called by `RecalculateReliabilityScore` job after any reservation status change
- **Key design:** Score stored on `customers` table for fast reads; recalculated asynchronously

### 4.7 StripeService

- **Responsibility:** Manages subscription lifecycle — create checkout session, handle webhooks (subscription activated, cancelled, payment failed).
- **Technology:** `stripe/stripe-php` library
- **Interfaces:** Called by `SubscriptionController`; receives webhooks via `StripeWebhookController`
- **Key design:** Stripe customer ID stored on `businesses` table; subscription status synced via webhooks

---

## 5. Data Architecture

### 5.1 Data Models

```
businesses
├── id: uuid PK
├── name: varchar(255) NOT NULL
├── email: varchar(255) UNIQUE NOT NULL
├── password: varchar(255) NOT NULL (hashed)
├── phone: varchar(20)
├── timezone: varchar(50) DEFAULT 'Europe/Paris'
├── subscription_status: enum('trial','active','cancelled') DEFAULT 'trial'
├── trial_ends_at: timestamptz NOT NULL
├── stripe_customer_id: varchar(100) NULLABLE
├── stripe_subscription_id: varchar(100) NULLABLE
├── created_at: timestamptz
└── updated_at: timestamptz

customers
├── id: uuid PK
├── phone: varchar(20) UNIQUE NOT NULL  -- E.164 (+33612345678)
├── reservations_count: int DEFAULT 0
├── shows_count: int DEFAULT 0
├── no_shows_count: int DEFAULT 0
├── reliability_score: decimal(5,2) NULLABLE  -- NULL = no history
├── last_calculated_at: timestamptz
└── created_at: timestamptz

reservations
├── id: uuid PK
├── business_id: uuid FK → businesses.id (CASCADE DELETE)
├── customer_id: uuid FK → customers.id
├── customer_name: varchar(255) NOT NULL
├── scheduled_at: timestamptz NOT NULL  -- date + time merged, stored in UTC
├── guests: smallint DEFAULT 1
├── notes: text NULLABLE
├── status: enum(
│     'pending_verification',   -- awaiting SMS confirmation
│     'pending_reminder',       -- phone-verified, awaiting reminder window
│     'confirmed',              -- client confirmed
│     'cancelled_by_client',    -- client cancelled via SMS link
│     'cancelled_no_confirmation', -- auto-cancelled (no reply)
│     'no_show',                -- business marked no-show
│     'show'                    -- business confirmed client showed up
│   ) DEFAULT 'pending_verification'
├── phone_verified: boolean DEFAULT false
├── confirmation_token: uuid UNIQUE NULLABLE
├── token_expires_at: timestamptz NULLABLE
├── reminder_2h_sent: boolean DEFAULT false
├── reminder_30m_sent: boolean DEFAULT false
├── status_changed_at: timestamptz  -- for 30min undo window
├── created_at: timestamptz
└── updated_at: timestamptz

sms_logs
├── id: uuid PK
├── reservation_id: uuid FK → reservations.id
├── business_id: uuid FK → businesses.id  -- for cost aggregation
├── phone: varchar(20) NOT NULL
├── type: enum('verification','reminder_2h','reminder_30m')
├── body: text  -- SMS content sent (for audit)
├── twilio_sid: varchar(100) NULLABLE
├── status: enum('queued','sent','delivered','failed') DEFAULT 'queued'
├── cost_eur: decimal(8,4) NULLABLE  -- populated via Twilio webhook
├── error_message: text NULLABLE
├── queued_at: timestamptz
├── sent_at: timestamptz NULLABLE
├── delivered_at: timestamptz NULLABLE
└── created_at: timestamptz

personal_access_tokens  -- Laravel Sanctum (auto-generated)
├── id: bigint PK
├── tokenable_type: varchar
├── tokenable_id: uuid
├── name: varchar(255)
├── token: varchar(64) UNIQUE
├── abilities: text NULLABLE
├── last_used_at: timestamptz NULLABLE
├── expires_at: timestamptz NULLABLE
└── created_at/updated_at: timestamptz
```

### 5.2 Database Choices

| Store | Technology | Rationale | What it stores |
|---|---|---|---|
| Primary DB | PostgreSQL 16 | ACID, UUID native, ENUM types, strong French hosting support | All application data |
| Queue backend | Redis 7 | Sub-ms job push, Laravel Queue native driver, reliable delivery | Job queue, failed jobs |
| Cache | Redis 7 (same instance) | Dashboard stats cache (30s TTL), rate limiting counters | Computed stats, rate limit buckets |
| Search | None | No full-text search needed at MVP scale | — |
| Vector / AI | None | No AI features in MVP | — |

**Key indexes:**

```sql
-- Scheduler query (runs every minute)
CREATE INDEX idx_reservations_scheduled_reminders
  ON reservations (scheduled_at, status, reminder_2h_sent, reminder_30m_sent)
  WHERE status IN ('pending_reminder', 'confirmed');

-- Phone lookup for reliability score display
CREATE UNIQUE INDEX idx_customers_phone ON customers (phone);

-- Dashboard query per business
CREATE INDEX idx_reservations_business_scheduled
  ON reservations (business_id, scheduled_at);

-- SMS cost aggregation per business per month
CREATE INDEX idx_sms_logs_business_created
  ON sms_logs (business_id, created_at);
```

### 5.3 Data Flow

**Write flow — Reservation creation (unverified number):**
```
1. Business submits POST /api/reservations
2. Sanctum middleware validates token → authenticated business
3. StoreReservationRequest validates fields (phone E.164, future date, etc.)
4. ReservationController:
   a. Find or create Customer by phone
   b. Create Reservation (status: pending_verification)
   c. Generate UUID confirmation_token, set token_expires_at
   d. Dispatch SendVerificationSms::class to queue
5. Return 201 with reservation + customer.reliability_score
6. Queue worker picks up job → calls TwilioSmsService::send()
7. Twilio delivers SMS → fires webhook → POST /webhooks/twilio
8. TwilioWebhookController updates sms_logs.status + cost_eur
```

**Write flow — Reservation creation (phone-verified):**
```
1–4. Same as above, except:
   b. Create Reservation (status: pending_reminder, phone_verified: true)
   d. NO verification SMS dispatched
   e. Dispatch ScheduleReminders::class to queue
      → Reminder jobs scheduled based on reliability score tier
5. Return 201
```

**Write flow — Client confirmation:**
```
1. Client GETs /c/{uuid} → ConfirmationController returns HTML page
2. Client POSTs /c/{uuid}/confirm with { action: 'confirm' }
3. ConfirmationController:
   a. Find reservation by confirmation_token
   b. Check token not expired and not already used
   c. Update reservation.status = 'confirmed'
   d. Invalidate token (set to null or mark used)
   e. Dispatch RecalculateReliabilityScore::class
4. Return confirmation HTML
```

**Write flow — Scheduler reminder trigger (every minute):**
```
Laravel Scheduler → ProcessScheduledReminders command:

1. Query: reservations WHERE status IN ('pending_reminder', 'confirmed')
         AND reminder_2h_sent = false
         AND scheduled_at BETWEEN now()+1h55m AND now()+2h05m
   → Dispatch SendReminderSms(type: '2h') for each

2. Query: reservations WHERE status IN ('pending_reminder', 'confirmed')
         AND reminder_30m_sent = false
         AND scheduled_at BETWEEN now()+25m AND now()+35m
   → Dispatch SendReminderSms(type: '30m') for each

3. Query: reservations WHERE status = 'pending_verification'
         AND token_expires_at < now()
   → Update status = 'cancelled_no_confirmation' for each

4. Query: reservations WHERE reminder_30m_sent = true
         AND status NOT IN ('confirmed', 'cancelled_by_client')
         AND scheduled_at < now()-15m
   → Update status = 'cancelled_no_confirmation' for each
```

**Read flow — Dashboard:**
```
1. Business GETs /api/dashboard?date=2026-03-12
2. Check Redis cache key dashboard:{business_id}:{date} (TTL: 30s)
   HIT → return cached response
   MISS → continue
3. Query PostgreSQL:
   - Reservations for that date, ordered by scheduled_at
   - Aggregate stats: confirmed count, pending count, cancelled count, no_show count
   - SMS cost sum for current month from sms_logs
4. Populate Redis cache (TTL: 30s)
5. Return JSON
```

---

## 6. API Design

### 6.1 API Style

**REST** [DECIDED — PRD explicit, GraphQL rejected]

- Versioning: URL prefix `/api/v1/` — use from day one to avoid breaking changes
- Pagination: offset-based for reservation lists (`?page=1&per_page=50`)
- Date/time: ISO 8601 with timezone (`2026-03-12T20:00:00+01:00`)
- Phone: E.164 format (`+33612345678`)

**Standard error format:**
```json
{
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "The phone field must be a valid E.164 phone number.",
    "details": {
      "phone": ["The phone field must be a valid E.164 phone number."]
    }
  }
}
```

**Error codes used:**
- `VALIDATION_ERROR` — 422
- `UNAUTHENTICATED` — 401
- `FORBIDDEN` — 403
- `NOT_FOUND` — 404
- `TOKEN_EXPIRED` — 410
- `TOKEN_USED` — 410
- `SUBSCRIPTION_REQUIRED` — 402
- `INVALID_WEBHOOK_SIGNATURE` — 403

### 6.2 Authentication Flow

```
Registration:
1. POST /api/v1/auth/register
   { name, email, password, business_name, phone }
   → Creates Business + hashed password
   → Sets trial_ends_at = now() + 14 days
   → Returns { token, business, trial_ends_at }

Login:
1. POST /api/v1/auth/login { email, password }
   → Validates credentials
   → Creates Sanctum personal access token
   → Returns { token, business }

Authenticated requests:
   Authorization: Bearer {token}
   → Sanctum middleware validates token
   → Injects authenticated Business into request

Token lifetime: 30 days (renewable on activity)
SPA mode: cookie-based auth also supported (same Sanctum config)

Webhook auth (Twilio):
   POST /api/v1/webhooks/twilio
   → Validate X-Twilio-Signature header using HMAC-SHA1
   → Reject with 403 if invalid

Webhook auth (Stripe):
   POST /api/v1/webhooks/stripe
   → Validate Stripe-Signature header
   → Reject with 400 if invalid
```

### 6.3 Full Route List

```
# Auth
POST   /api/v1/auth/register
POST   /api/v1/auth/login
POST   /api/v1/auth/logout          [auth]

# Reservations
POST   /api/v1/reservations         [auth, subscription]
GET    /api/v1/reservations         [auth] ?date= | ?week=
GET    /api/v1/reservations/{id}    [auth]
PATCH  /api/v1/reservations/{id}/status  [auth]  { status: show|no_show }
DELETE /api/v1/reservations/{id}    [auth]

# Customers
GET    /api/v1/customers/lookup     [auth] ?phone=+33612345678

# Dashboard
GET    /api/v1/dashboard            [auth] ?date=

# Subscription (Stripe)
POST   /api/v1/subscription/checkout    [auth]  → Stripe Checkout redirect URL
GET    /api/v1/subscription             [auth]  → current status + next invoice

# Confirmation (public, no auth)
GET    /c/{token}                        → HTML confirmation page
POST   /c/{token}/confirm               { action: confirm|cancel }

# Webhooks (no Sanctum auth, signature-validated)
POST   /api/v1/webhooks/twilio
POST   /api/v1/webhooks/stripe
```

---

## 7. Billing Architecture (Stripe)

### Model

Two separate billing items on the same Stripe customer:

| Item | Type | Amount |
|---|---|---|
| ZeroNoShow Standard | Stripe Subscription (recurring) | 19€/month |
| SMS usage | Stripe Invoice Item (metered, billed monthly) | ~0.07€/SMS |

**Flow:**
```
1. Business clicks "Subscribe" in dashboard
2. POST /api/v1/subscription/checkout
   → StripeService::createCheckoutSession()
   → Returns Stripe Checkout URL
3. Business completes Stripe Checkout (CB details)
4. Stripe fires webhook: checkout.session.completed
   → StripeWebhookController updates business.subscription_status = 'active'
   → Stores stripe_customer_id + stripe_subscription_id

5. Each SMS sent:
   → SmsService records cost in sms_logs.cost_eur
   → At month end, cron job:
      a. Aggregates sms_logs.cost_eur per business for the month
      b. Creates Stripe InvoiceItem via API
      c. Stripe auto-invoices on subscription renewal date

6. Payment failed webhook:
   → business.subscription_status = 'past_due'
   → Dashboard shows payment warning (no immediate block for 3 days grace)

7. Subscription cancelled webhook:
   → business.subscription_status = 'cancelled'
   → Reservation creation blocked
```

**[TBD]** — Exact Stripe metered billing vs manual invoice item approach. Metered billing is more automated but requires more Stripe configuration. Manual invoice items are simpler to implement for MVP.

---

## 8. Infrastructure & Deployment

### 8.1 Recommended Stack [RECOMMENDED]

**DigitalOcean + Laravel Forge**

| Component | Service | Cost estimate |
|---|---|---|
| App server | DigitalOcean Droplet (2 vCPU, 2GB RAM) | ~18€/month |
| Database | DigitalOcean Managed PostgreSQL (1GB) | ~15€/month |
| Redis | DigitalOcean Managed Redis (1GB) | ~15€/month |
| Backups | DigitalOcean Backups (auto) | ~4€/month |
| **Total infra** | | **~52€/month** |

Forge manages: Nginx, PHP-FPM, queue workers (supervisor), scheduler (cron), SSL (Let's Encrypt), deployments.

### 8.2 Environment Strategy

| Environment | Purpose | Data | Deploy trigger |
|---|---|---|---|
| Local | Development | SQLite or local PG | Manual |
| Staging | Pre-prod validation | Seeded fixtures | Push to `develop` branch |
| Production | Live traffic | Real data | Manual tag / Forge deploy button |

### 8.3 CI/CD Pipeline

```
GitHub Push → GitHub Actions:

1. lint         → php-cs-fixer + ESLint
2. test         → php artisan test (Feature + Unit)
3. build        → npm run build (Vite)
4. deploy       → Forge webhook (production: manual trigger only)
```

Minimum test gates before deploy:
- All Feature tests pass (critical paths: reservation creation, SMS dispatch, confirmation)
- No PHP syntax errors (`php -l`)

### 8.4 Local Development

```
# docker-compose.yml provides:
services:
  app:     Laravel (PHP-FPM + Nginx)
  worker:  php artisan queue:work
  scheduler: crond → php artisan schedule:run
  db:      PostgreSQL 16
  redis:   Redis 7
  mailpit: Local email capture (trial expiry emails)
```

### 8.5 Observability

| Concern | Tool | What it tracks |
|---|---|---|
| Application logs | Laravel Log (daily files) → stdout | Errors, SMS failures, webhook events |
| Queue monitoring | Laravel Horizon (Redis) | Queue depth, job throughput, failed jobs |
| Error tracking | Sentry (free tier) | Exceptions with stack traces |
| Uptime | Better Uptime or UptimeRobot (free) | HTTP check every 60s, SMS alert on down |
| Server metrics | DigitalOcean built-in | CPU, memory, disk |

**Alerts that must fire:**
- SMS job fails after 3 retries → Sentry alert + dashboard badge for business
- Scheduler hasn't run in > 2 minutes → UptimeRobot alert on `/api/v1/health`
- Queue depth > 50 jobs → Horizon alert

---

## 9. Security Architecture

### Authentication & Authorization

- **Sanctum tokens**: 30-day lifetime, revoked on logout, stored hashed in DB
- **Business scoping**: Every query filtered by `business_id` from authenticated token — no cross-business data leakage possible
- **Public confirmation tokens**: UUIDv4, single-use, time-limited — no brute-force risk at this token entropy
- **Twilio webhooks**: HMAC-SHA1 signature on every request via `twilio/sdk` helper
- **Stripe webhooks**: Stripe-Signature header validated via `stripe/stripe-php`
- **No RBAC needed for MVP**: One user per business account

### Input Validation

All inputs validated at the HTTP Request layer (Laravel Form Requests), not just at the DB:
- Phone: E.164 regex + `libphonenumber` validation [RECOMMENDED]
- Date: must be a future date
- UUID tokens: format + existence check before any DB write
- Webhook payloads: signature checked before payload parsed

### Rate Limiting

| Endpoint | Limit | Window |
|---|---|---|
| `POST /auth/login` | 10 attempts | per IP per 15 min |
| `POST /auth/register` | 5 attempts | per IP per hour |
| `POST /c/{token}/confirm` | 10 attempts | per token |
| `POST /api/v1/reservations` | 60 requests | per business per minute |
| `POST /webhooks/*` | 200 requests | per IP per minute |

### Secrets Management

- All secrets in `.env` (never committed)
- Forge encrypts `.env` in its dashboard
- Secrets: `TWILIO_ACCOUNT_SID`, `TWILIO_AUTH_TOKEN`, `TWILIO_FROM`, `STRIPE_SECRET`, `STRIPE_WEBHOOK_SECRET`, `APP_KEY`

### HTTPS

- TLS 1.2+ enforced via Nginx (Forge default)
- Let's Encrypt auto-renewal via Forge
- HSTS header: `Strict-Transport-Security: max-age=31536000`

### GDPR Minimal Compliance (MVP)

- Phone numbers treated as personal data
- First SMS includes: "Reply STOP to opt out of reminders" [RECOMMENDED]
- Customer deletion: anonymizes phone → `DELETED_{hash}`, preserves aggregate counts for score integrity
- Data retention: `sms_logs` purged after 90 days via scheduler
- No data sold or shared with third parties (except Twilio for delivery)

---

## 10. Project Structure

```
zeronoshow/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Auth/
│   │   │   │   ├── RegisterController.php
│   │   │   │   └── LoginController.php
│   │   │   ├── ReservationController.php
│   │   │   ├── ConfirmationController.php
│   │   │   ├── DashboardController.php
│   │   │   ├── SubscriptionController.php
│   │   │   └── Webhook/
│   │   │       ├── TwilioWebhookController.php
│   │   │       └── StripeWebhookController.php
│   │   ├── Middleware/
│   │   │   └── RequireActiveSubscription.php
│   │   └── Requests/
│   │       ├── StoreReservationRequest.php
│   │       └── UpdateReservationStatusRequest.php
│   ├── Jobs/
│   │   ├── SendVerificationSms.php
│   │   ├── SendReminderSms.php         -- type: '2h' | '30m'
│   │   ├── RecalculateReliabilityScore.php
│   │   └── SyncMonthlySmsCostToStripe.php
│   ├── Models/
│   │   ├── Business.php
│   │   ├── Customer.php
│   │   ├── Reservation.php
│   │   └── SmsLog.php
│   ├── Observers/
│   │   └── ReservationObserver.php    -- triggers score recalc on status change
│   ├── Services/
│   │   ├── Contracts/
│   │   │   └── SmsServiceInterface.php
│   │   ├── TwilioSmsService.php
│   │   ├── ReliabilityScoreService.php
│   │   └── StripeService.php
│   ├── Console/
│   │   ├── Commands/
│   │   │   ├── ProcessScheduledReminders.php
│   │   │   ├── AutoCancelExpiredReservations.php
│   │   │   └── PurgeSmsLogs.php        -- 90-day retention
│   │   └── Kernel.php
│   └── Providers/
│       └── AppServiceProvider.php     -- bind SmsServiceInterface → TwilioSmsService
│
├── database/
│   ├── migrations/
│   │   ├── 2026_03_12_000001_create_businesses_table.php
│   │   ├── 2026_03_12_000002_create_customers_table.php
│   │   ├── 2026_03_12_000003_create_reservations_table.php
│   │   └── 2026_03_12_000004_create_sms_logs_table.php
│   └── seeders/
│       └── DatabaseSeeder.php
│
├── resources/
│   └── js/                            -- Vue.js 3 SPA
│       ├── components/
│       │   ├── ReservationForm.vue
│       │   ├── ReservationList.vue
│       │   ├── ReservationRow.vue
│       │   └── ReliabilityBadge.vue
│       ├── pages/
│       │   ├── Dashboard.vue
│       │   ├── Login.vue
│       │   ├── Register.vue
│       │   └── Subscription.vue
│       ├── composables/
│       │   ├── useReservations.js
│       │   └── useAuth.js
│       └── stores/
│           └── auth.js                -- Pinia store
│
├── routes/
│   ├── api.php
│   └── web.php                        -- serves SPA + /c/{token} pages
│
├── tests/
│   ├── Feature/
│   │   ├── ReservationTest.php
│   │   ├── ConfirmationTest.php
│   │   ├── ReminderSchedulerTest.php
│   │   └── WebhookTest.php
│   └── Unit/
│       ├── ReliabilityScoreServiceTest.php
│       └── SmsServiceTest.php
│
├── docker/
│   ├── docker-compose.yml
│   └── nginx.conf
│
├── docs/
│   ├── PRD.md
│   └── architecture.md                -- this file
│
├── .env.example
├── .github/workflows/ci.yml
└── forge-deploy.sh
```

**Organizing principle:** Layer-first for backend (Controllers / Jobs / Models / Services) — appropriate for a small monolith where features are few and cross-cutting. Feature-first for the Vue.js SPA (components / pages / composables).

---

## 11. Architectural Decision Records

### ADR-001: Modular Monolith over Microservices
- **Status:** Accepted
- **Context:** Team is small (1–2 devs), MVP timeline is 4–6 weeks, scale target is 100 businesses in Year 1.
- **Decision:** Single Laravel application for API + workers + scheduler.
- **Rationale:** Microservices add networking complexity, DevOps overhead, and distributed transaction problems. None of those trade-offs make sense below 10k users.
- **Trade-offs:** Cannot independently scale SMS processing vs API. Acceptable — a single 4-vCPU droplet handles >10k SMS/day.
- **Consequences:** When V2 integrations (TheFork, Doctolib) arrive, they remain modules within the monolith, not separate services.

---

### ADR-002: PostgreSQL as sole datastore
- **Status:** Accepted
- **Context:** Need ACID guarantees for reservation status transitions and score updates. UUID primary keys. ENUM types for status columns.
- **Decision:** PostgreSQL 16 as the only persistent datastore.
- **Rationale:** Strong consistency required — a no-show that triggers a score update must be atomic. Redis is cache/queue only, not a persistent store.
- **Trade-offs:** Slightly more complex to shard at very high scale. Not a concern for Year 1.
- **Consequences:** All business logic can use DB transactions. No eventual consistency edge cases.

---

### ADR-003: Redis for queue and cache only
- **Status:** Accepted
- **Context:** Need a reliable queue backend for SMS jobs. Need a fast cache for dashboard stats (polled every 30s per business).
- **Decision:** Redis for queue (Laravel Queue driver) and cache (30s TTL dashboard stats). Managed DigitalOcean Redis.
- **Rationale:** Laravel Queue with Redis is battle-tested. Database queue driver would add row-level locks on `jobs` table — unnecessary complexity.
- **Trade-offs:** Adds a second infrastructure dependency. Managed service removes ops burden.
- **Consequences:** Queue workers connect to Redis. If Redis is down, SMS jobs queue on the DB driver as fallback [RECOMMENDED to configure].

---

### ADR-004: Twilio as SMS provider with abstracted interface
- **Status:** Accepted
- **Context:** Need reliable SMS delivery to French mobile numbers. Multiple providers exist (Twilio, Vonage, Sinch, OVH SMS).
- **Decision:** Twilio as primary. `SmsServiceInterface` abstracts the implementation.
- **Rationale:** Twilio has the best PHP SDK, French delivery rates, and webhook support. Interface means zero-cost switch to Vonage if Twilio prices increase or delivery degrades.
- **Trade-offs:** Twilio cost (~0.07€/SMS) passed directly to customer — transparent pricing model makes this acceptable.
- **Consequences:** `TwilioSmsService` implements `SmsServiceInterface`. Any other provider requires only a new implementation class, registered in `AppServiceProvider`.

---

### ADR-005: Laravel Sanctum over JWT (Passport)
- **Status:** Accepted
- **Context:** Need stateless API auth for the Vue.js SPA.
- **Decision:** Laravel Sanctum with Personal Access Tokens.
- **Rationale:** Sanctum is simpler than Passport (no OAuth server), tokens stored in DB (revocable immediately), first-party SPA support built-in. JWT tokens are stateless but not revocable without a blacklist — unnecessary complexity.
- **Trade-offs:** Token lookup requires a DB read on every request. Negligible at this scale.
- **Consequences:** `Authorization: Bearer {token}` on all authenticated API calls. Tokens expire after 30 days.

---

### ADR-006: 30-second polling over WebSockets for dashboard
- **Status:** Accepted
- **Context:** Dashboard needs to show reservation status updates (client confirmed, auto-cancelled) without requiring a page refresh.
- **Decision:** Vue.js polls `GET /api/v1/dashboard` every 30 seconds.
- **Rationale:** WebSockets (Laravel Reverb/Pusher) add infrastructure complexity. At 100 businesses × 1 request/30s = ~3 requests/second — trivially handled. Redis cache makes these reads cheap.
- **Trade-offs:** Up to 30-second delay for status updates. Acceptable for this use case — a business doesn't need millisecond accuracy.
- **Consequences:** V2 can upgrade to WebSockets (Laravel Reverb) with no frontend API changes — just replace polling with a socket event listener.

---

### ADR-007: Stripe for billing (subscription + SMS cost aggregation)
- **Status:** Accepted
- **Context:** Need to charge 19€/month subscription + variable SMS costs per business.
- **Decision:** Stripe Subscription for flat fee. Monthly Stripe Invoice Items for SMS cost aggregation (cron job sums `sms_logs.cost_eur` per business at month end).
- **Rationale:** Stripe metered billing (Usage Records API) is more automated but requires more complex Stripe configuration. Invoice Items approach is simpler to implement and debug for MVP.
- **Trade-offs:** Requires a reliable end-of-month cron job. If it fails, SMS costs not billed. Mitigation: idempotent job with logging.
- **Consequences:** `SyncMonthlySmsCostToStripe` job runs on the 1st of each month, creates Invoice Items for the previous month, then Stripe auto-invoices on subscription renewal.

---

### ADR-008: DigitalOcean + Laravel Forge for hosting
- **Status:** Recommended [RECOMMENDED — hosting TBD per PRD]
- **Context:** Need a simple, affordable hosting setup for a solo developer.
- **Decision:** DigitalOcean Managed services (PostgreSQL + Redis) + Droplet managed by Laravel Forge.
- **Rationale:** Forge handles Nginx, PHP-FPM, queue workers (Supervisor), SSL, deployments. Managed DB and Redis eliminate backup/patching overhead. Total cost ~52€/month well within margins.
- **Trade-offs:** Not multi-region, no auto-scaling. Acceptable for Year 1 scale.
- **Consequences:** Single-server deployment. Vertical scale (resize droplet) before considering horizontal scale.

---

## 12. Non-Functional Requirements Mapping

| NFR | Target (PRD) | Architecture Response |
|---|---|---|
| SMS dispatch latency | Within 30s of reservation creation | Queue worker with Redis driver; Twilio API avg <1s; end-to-end <30s |
| Confirmation page load | < 2 seconds | Served by Laravel (Blade), no JS bundle required; Nginx gzip + browser cache |
| Dashboard load | < 1 second for 100 reservations | Redis cache (30s TTL); indexed PG query; ~5ms on local network |
| Reminder timing accuracy | 2h ±5min, 30min ±2min | Scheduler runs every minute; query window ±5min built into command |
| Score recalculation | Within 5 minutes of status change | `RecalculateReliabilityScore` job dispatched immediately on status change; queue lag <5min under normal load |
| Zero silent failures | All SMS errors logged | Job `failed()` hook logs to `sms_logs`; Sentry captures exception; Horizon tracks failed jobs |
| Onboarding time | < 5 minutes | Registration: 4 fields; first reservation: 5 fields; SMS auto-dispatched — no config needed |
| Uptime | Implicit SaaS expectation | DigitalOcean SLA: 99.99% for Managed services; Droplet: 99.95%; UptimeRobot alerting |
| GDPR data deletion | On request | Customer deletion anonymizes phone, preserves aggregate counts |

---

## 13. Phase Alignment

| Phase | Components to build | Architecture readiness check |
|---|---|---|
| **Phase 1** — Foundation (wk 1–2) | Auth, Reservation CRUD, SmsService, Verification SMS, Confirmation flow, Vue SPA scaffold | ✓ DB schema finalized before migrations; ✓ `.env.example` complete; ✓ docker-compose working; ✓ CI pipeline green |
| **Phase 2** — Reminders & Scoring (wk 3) | ReliabilityScoreService, Scheduler command, ReminderSms jobs, Auto-cancellation, Twilio webhook | ✓ Scheduler cron configured in Forge staging; ✓ Queue workers running; ✓ Twilio webhook URL accessible |
| **Phase 3** — Dashboard & Billing (wk 4) | Full dashboard UI, No-show marking, Stripe integration, Trial enforcement | ✓ Stripe test mode webhook configured; ✓ Redis cache layer active; ✓ Horizon deployed |
| **Phase 4** — Hardening & Launch (wk 5–6) | Rate limiting, HMAC validation, edge cases, smoke tests, prod deploy | ✓ Sentry configured; ✓ UptimeRobot alert active; ✓ Production `.env` set; ✓ DNS + SSL live |

---

## 14. Open Questions & Risks

| Question / Risk | Impact | Recommended Action | Target |
|---|---|---|---|
| Hosting provider final choice (DigitalOcean recommended, TBD) | Medium | Confirm before Phase 4; Forge account needed early for staging | Phase 1 |
| Stripe metered billing vs manual Invoice Items for SMS costs | Low | Start with manual Invoice Items (simpler); migrate to metered if volume grows | Phase 3 |
| Twilio sender ID in France (long code vs short code vs alphanumeric) | High | Test with French mobile numbers in Phase 1; alphanumeric sender ("ZeroNoShow") increases open rate but requires Twilio registration | Phase 1 |
| STOP opt-out handling (French ARCEP regulations on commercial SMS) | High | Include STOP instruction in first SMS; handle STOP reply via Twilio webhook → mark customer as opted-out | Phase 2 |
| Legal review of cross-business reliability scoring | Medium | Consult a GDPR lawyer before reaching 50 businesses; draft privacy policy before launch | Phase 4 |
| Email provider for trial expiry notifications | Low | Resend.com or Mailgun (both have generous free tiers); add to tech stack before Phase 3 | Phase 3 |
| Token collision on UUID confirmation links | Very Low | UUIDv4 collision probability negligible; add UNIQUE constraint on DB as safety net (already in schema) | Done |
