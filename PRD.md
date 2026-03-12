# PRD — ZeroNoShow

**Smart No-Show Prevention via SMS Reliability Scoring**

Version: 2.0
Date: 2026-03-12
Status: MVP Definition

---

## 1. Executive Summary

### Problem Statement

Appointment-based businesses lose significant revenue to no-shows every week. A single no-show for a table of 2 at a restaurant represents ~70€ of lost revenue, plus wasted food preparation. Existing solutions require credit card deposits or full reservation system migrations — creating friction that drives clients away before they can solve the problem.

### Proposed Solution

ZeroNoShow is a lightweight SMS overlay that plugs on top of any existing reservation workflow. Businesses enter only the reservations they're unsure about (unknown clients, online bookings), and the system sends confirmation and smart reminders based on a cross-business reliability score tied to each phone number.

### Core Value Proposition

- No reservation system migration required
- No credit card deposits needed
- 3–4 no-shows avoided = subscription paid for itself
- Reliability scores improve automatically as more businesses join the platform (network effect)

### MVP North Star

> Enable any appointment-based business to start reducing no-shows in under 5 minutes, without changing their existing workflow.

### Success Criteria

| KPI | Target |
|---|---|
| No-show rate reduction | ≥ 50% for active users |
| SMS confirmation rate | ≥ 85% across all reservations |
| Business onboarding time | ≤ 5 minutes from signup to first SMS sent |
| Paying businesses at 3 months | ≥ 20 |
| Paying businesses at 12 months | ≥ 100 |
| Trial-to-paid conversion rate | ≥ 40% |

---

## 2. Mission & Principles

**Mission:** Eliminate appointment no-shows without adding friction for businesses or their clients.

**Core Principles:**

1. **Simplicity first** — A business should be able to use ZeroNoShow without reading documentation.
2. **Non-intrusive** — We do not replace existing tools. We augment them.
3. **Client-side zero friction** — Clients never install anything. SMS only.
4. **Network-powered reliability** — Every business on the platform improves the score system for all others.
5. **Transparent pricing** — No hidden costs. SMS billed at cost, subscription fixed.

---

## 3. Target Users

### Persona 1 — Marc, Restaurant Owner

- **Role:** Owner/manager of a 40-cover restaurant
- **Technical level:** Low — uses a paper reservation book or basic app
- **Workflow:** Takes reservations by phone, occasionally via TheFork or website
- **Pain points:** 3–5 no-shows per week, wastes prepped food, loses 200–350€/week in revenue
- **Needs:** Something that works immediately, doesn't require training staff, costs less than one lost table

### Persona 2 — Sophie, Independent Hairdresser

- **Role:** Solo practitioner, 30–40 appointments/week
- **Technical level:** Low-medium — uses Google Calendar or a booking app
- **Workflow:** Mixes phone and online bookings
- **Pain points:** 2–3 no-shows/week = 2–3 hours of dead time = 60–90€ lost
- **Needs:** Automated reminders without managing a complex system

### Persona 3 — Dr. Lefebvre, General Practitioner

- **Role:** Doctor in private practice
- **Technical level:** Low — uses a secretary or Doctolib
- **Workflow:** Mostly Doctolib for known patients, phone for new patients
- **Pain points:** New patients have high no-show rates, impossible to fill slots last minute
- **Needs:** Verify that new patients will actually show up before the appointment

### Persona 4 — Client (Passive User)

- **Role:** End customer making an appointment
- **Technical level:** Any
- **Workflow:** Receives an SMS, taps a link, confirms or cancels
- **Needs:** Nothing — no account, no app, no friction

---

## 4. MVP Scope

### In Scope

**Core Functionality**
- [x] Business account creation and authentication
- [x] Manual reservation creation via dashboard (name, phone, date, time, guests, notes)
- [x] Phone-verified number flow: if client called from visible number and confirmed orally → skip verification SMS
- [x] SMS verification for manually entered or online-sourced phone numbers
- [x] Smart reminder system triggered by reliability score:
  - Score > 90%: no reminder
  - Score 70–90%: 1 reminder 2h before appointment
  - Score < 70%: reminder 2h before + reminder 30 min before
- [x] Confirmation link in SMS (one-click, no account required for client)
- [x] Automatic cancellation if required confirmation not received before configurable deadline
- [x] Reliability score per phone number (cross-business, formula: `shows / total_reservations`)
- [x] Dashboard: today's reservations, statuses (Pending / Confirmed / Cancelled / No-Show)
- [x] Daily and weekly reservation views
- [x] 14-day free trial (SMS billed at cost from day 1)
- [x] Subscription management (19€/month)
- [x] Pay-per-SMS billing with transparent per-message cost

**Technical**
- [x] Laravel 12 backend with queue-based SMS scheduling
- [x] Vue.js 3 frontend dashboard
- [x] PostgreSQL database
- [x] Twilio or Vonage SMS integration
- [x] Laravel Sanctum authentication
- [x] Laravel Scheduler for reminder triggers

**Deployment**
- [x] Single-tenant SaaS (one account per business)
- [x] Environment-based configuration (API keys, SMS provider credentials)

### Out of Scope (Deferred to V2+)

- [ ] **Waitlist system** — Valid feature but not needed to prove core value
- [ ] **WhatsApp / Telegram channel** — Reduces SMS cost but adds integration complexity; deferred until post-MVP
- [ ] **Third-party integrations** (TheFork, Doctolib, Planity, Calendly, Google Calendar) — High value, high effort; deferred to V2
- [ ] **Zapier / Make connectors** — Deferred to V2 alongside integrations
- [ ] **Advanced analytics** — Basic dashboard sufficient for MVP
- [ ] **Multi-location / team management** — Single business unit per account for MVP
- [ ] **Mobile app** — Web dashboard sufficient for MVP

### Non-Goals

ZeroNoShow is **not** a reservation system. It will never compete with TheFork, Doctolib, or Calendly. Businesses keep their existing tools — ZeroNoShow only handles the no-show problem. The product will never require clients to create an account, install an app, or provide payment information.

---

## 5. User Stories

### US-01 — Quick Reservation Entry
> As Marc (restaurant owner), I want to create a reservation in under 60 seconds, so that I don't interrupt my workflow during a busy phone call.

**Acceptance criteria:**
- Form has ≤ 6 fields: name, phone, date, time, guests (optional), notes (optional)
- Reservation saved and SMS triggered within 5 seconds of form submission
- Checkbox "Number confirmed by phone" skips verification SMS and schedules reminder directly

---

### US-02 — Automatic SMS Confirmation
> As Sophie (hairdresser), I want the system to automatically send a confirmation SMS after I create a reservation, so that I don't have to call clients back.

**Acceptance criteria:**
- SMS sent within 30 seconds of reservation creation
- SMS contains date, time, and a one-click confirmation link
- Link expires after a configurable delay (default: 24h or 2h before appointment, whichever comes first)
- Confirmation updates reservation status to "Confirmed" in dashboard within 10 seconds of client action

---

### US-03 — Smart Reminders Based on Reliability
> As Dr. Lefebvre, I want unknown clients with no score history to receive multiple reminders, so that I can minimize no-shows from new patients.

**Acceptance criteria:**
- Clients with no prior score are treated as score < 70% (2 reminders)
- Reminder sent exactly 2h ± 5 minutes before appointment time
- Second reminder sent exactly 30 min ± 2 minutes before appointment time
- If reminder requires confirmation and none received within 15 minutes of appointment: status auto-set to "Cancelled — No Confirmation"

---

### US-04 — Cross-Business Reliability Score
> As Marc, I want to know if a new client has a history of no-shows at other businesses, so that I can decide whether to overbook or call them personally.

**Acceptance criteria:**
- Score displayed on reservation detail page as a percentage and a label (Reliable / Average / At Risk)
- Score is calculated across all businesses on the platform using the phone number
- Score visible immediately when creating a reservation if the number is already in the system
- Score updates within 5 minutes of a reservation being marked No-Show or Show

---

### US-05 — One-Click Client Confirmation
> As a client, I want to confirm my appointment by clicking a single link in an SMS, so that I don't need to call back or install anything.

**Acceptance criteria:**
- Link works on any mobile browser without login
- Confirmation page loads in < 2 seconds
- Client can also cancel from the same page
- After action: client sees a clear confirmation message ("Your appointment is confirmed" / "Your appointment has been cancelled")

---

### US-06 — Dashboard Overview
> As Marc, I want to see all today's reservations and their confirmation status at a glance, so that I can plan my day.

**Acceptance criteria:**
- Dashboard loads in < 1 second for up to 100 reservations/day
- Reservations grouped by status with color coding
- Switching between daily and weekly view takes < 500ms

---

### US-07 — Trial to Paid Conversion
> As a new business owner, I want to try the product free for 14 days without entering my card, so that I can validate it works before committing.

**Acceptance criteria:**
- Account creation requires only: business name, email, password, phone number
- Account creation completes in < 2 minutes
- Trial starts automatically, no credit card required
- SMS costs billed separately from day 1 with transparent per-message pricing displayed in dashboard
- Trial expiry email sent 48h before end of trial

---

### US-08 (Technical) — Reliable SMS Scheduling
> As a developer, I want all SMS jobs to be queued and retried on failure, so that reminders are never silently dropped.

**Acceptance criteria:**
- All SMS sends handled via Laravel queues (not synchronous)
- Failed jobs retried up to 3 times with exponential backoff
- Failed jobs after 3 retries logged to `sms_logs` with error detail and alert triggered
- SMS delivery status (delivered / failed) stored in `sms_logs` via provider webhook within 60 seconds of send

---

## 6. Core Architecture & Patterns

### Architecture Style
Monolith with queued background jobs. Single Laravel application serving both API (for Vue.js SPA) and background processing.

### Data Flow

```
Business creates reservation (dashboard)
        │
        ▼
Laravel API → ReservationController
        │
        ├─ Is number phone-verified? ─── YES → schedule reminder job
        │                                        based on reliability score
        └─ NO → dispatch VerificationSmsJob
                        │
                        ▼
                  Twilio/Vonage API → SMS to client
                        │
                        ▼
                  Client clicks link → ConfirmationController
                        │
                        └─ Update reservation status
                           Update reliability score (async job)

Laravel Scheduler (every minute)
        │
        ├─ Check reservations needing 2h reminder → dispatch ReminderJob
        ├─ Check reservations needing 30min reminder → dispatch ReminderJob
        └─ Check unconfirmed reservations past deadline → auto-cancel

Twilio/Vonage Webhook → SmsStatusController
        │
        └─ Update sms_logs.status (delivered/failed)
```

### Key Design Patterns

- **Command/Job pattern** for all async operations (SMS sending, score recalculation)
- **Repository pattern** for data access (testable, swappable)
- **Observer pattern** on Reservation model to trigger score recalculation on status change
- **Single Page Application** (Vue.js 3 SPA + Laravel API)

### Directory Structure (Backend)

```
app/
├── Http/Controllers/
│   ├── ReservationController.php
│   ├── ConfirmationController.php
│   ├── DashboardController.php
│   └── Webhook/SmsStatusController.php
├── Jobs/
│   ├── SendVerificationSms.php
│   ├── SendReminderSms.php
│   └── RecalculateReliabilityScore.php
├── Models/
│   ├── Business.php
│   ├── Customer.php
│   ├── Reservation.php
│   └── SmsLog.php
├── Services/
│   ├── SmsService.php          # Twilio integration
│   └── ReliabilityScoreService.php
└── Console/Commands/
    └── ProcessScheduledReminders.php
```

---

## 7. Feature Specifications

### Feature 1 — Reservation Creation

**Purpose:** Allow businesses to create a reservation and trigger the no-show prevention workflow.

**Behavior:**
1. Business fills form: name, phone, date, time, guests (optional), notes (optional)
2. Business checks "Number confirmed by phone" if client called from visible number and orally confirmed the SMS number
3. On save:
   - If "confirmed by phone": reservation status = `pending_reminder`, schedule reminder based on score
   - If not: reservation status = `pending_verification`, dispatch `SendVerificationSms` job
4. Verification SMS contains a secure tokenized link (UUID, single-use, 24h TTL or TTL until 2h before appointment)

**Edge Cases:**
- Phone number already exists in system: display reliability score immediately in form
- Appointment is < 2h away: skip verification SMS, send reminder immediately with confirmation request
- Appointment is < 30min away: skip all SMS, mark as "Too late to confirm"
- Invalid phone format: validate E.164 format before save, show inline error

**Dependencies:** SmsService, ReliabilityScoreService

---

### Feature 2 — Reliability Score

**Purpose:** Give businesses predictive insight into whether a client will show up, and automatically calibrate reminder intensity.

**Behavior:**
- Score = `shows / (shows + no_shows)` across all businesses on the platform for a given phone number
- Score tiers:
  - `≥ 90%` → **Reliable** (green) → no reminder
  - `70–89%` → **Average** (orange) → 1 reminder 2h before
  - `< 70%` → **At Risk** (red) → 2 reminders (2h + 30min)
  - No history → treated as **At Risk**
- Score recalculated asynchronously within 5 minutes of any reservation status change
- Score visible:
  - In reservation creation form (if number known)
  - In reservation detail view
  - In reservation list (as colored badge)

**Edge Cases:**
- First reservation ever from a phone number: score = null, treated as At Risk
- Client confirms but doesn't show: status manually set to No-Show by business → score drops
- Business accidentally marks No-Show: undo available within 30 minutes

**Dependencies:** `customers` table, `RecalculateReliabilityScore` job

---

### Feature 3 — Smart Reminder System

**Purpose:** Automatically send the right number of reminders based on reliability score, without business intervention.

**Behavior:**
- Laravel Scheduler runs every minute, checks `reservations` for upcoming reminders
- Reminder SMS sent with confirmation request if score ≤ 90%
- Reminder at 2h: requires confirmation reply → if no confirmation within 90 minutes, second reminder sent (for At Risk)
- Reminder at 30min: if no confirmation received → auto-cancel after 15 minutes
- Business notified via dashboard (badge update) on auto-cancellation

**Edge Cases:**
- Client confirms during 2h reminder → 30min reminder cancelled
- Client cancels → slot freed, reservation status = `cancelled_by_client`
- SMS delivery fails (webhook reports failure) → retry once, log error, alert business via dashboard notification

**Dependencies:** Laravel Scheduler, SmsService, queue workers

---

### Feature 4 — Confirmation Flow (Client Side)

**Purpose:** Enable clients to confirm or cancel in one tap, with zero account requirements.

**Behavior:**
- URL format: `zeronoshow.fr/c/{uuid}`
- Page loads: shows business name, date, time
- Two buttons: "Confirm my appointment" / "Cancel my appointment"
- Action recorded, reservation status updated, reliability score queued for recalculation
- Response page: clear confirmation of action in client's language (French for MVP)
- Link becomes invalid after action or after expiry

**Edge Cases:**
- Link already used: show "You've already confirmed/cancelled this appointment"
- Link expired: show "This link has expired. Please contact [business name]."
- Client clicks confirm then cancel (or vice versa): last action wins if within 30-minute window

---

### Feature 5 — Dashboard

**Purpose:** Give businesses a clear view of their reservation status for the day/week.

**Behavior:**
- Default view: today's reservations sorted by time
- Status columns: Pending Verification / Confirmed / At Risk (no confirmation yet) / Cancelled / No-Show
- Toggle: Daily / Weekly view
- Each reservation row shows: client name, time, guests, reliability score badge, status, action buttons (mark No-Show, mark Show, edit)
- Real-time status updates via polling every 30 seconds (WebSocket in V2)

---

## 8. Technology Stack

| Layer | Technology | Version | Notes |
|---|---|---|---|
| Backend | Laravel | 12.x | API + queue workers + scheduler |
| Frontend | Vue.js | 3.x | SPA, Composition API |
| Database | PostgreSQL | 16.x | Primary datastore |
| Auth | Laravel Sanctum | — | SPA token-based auth |
| SMS Provider | Twilio | — | Vonage as fallback if needed |
| Queue | Laravel Queue + Redis | — | Redis for queue backend |
| Scheduler | Laravel Scheduler | — | Cron every minute |
| Frontend build | Vite | — | Standard Laravel/Vue setup |
| CSS | Tailwind CSS | 3.x | [Assumed] |

**Key Libraries:**
- `twilio/sdk` — SMS sending and webhook handling
- `laravel/sanctum` — API authentication
- `spatie/laravel-permission` — Role management (business admin)

**Explicitly Rejected:**
- Next.js — Heavier than needed, team prefers Vue.js
- Credit card-based anti-no-show tools — Against product philosophy
- GraphQL — REST sufficient for MVP complexity

---

## 9. Database Schema

### `businesses`
```sql
id              UUID PRIMARY KEY
name            VARCHAR(255) NOT NULL
email           VARCHAR(255) UNIQUE NOT NULL
phone           VARCHAR(20)
timezone        VARCHAR(50) DEFAULT 'Europe/Paris'
subscription_status  ENUM('trial', 'active', 'cancelled')
trial_ends_at   TIMESTAMP
created_at      TIMESTAMP
updated_at      TIMESTAMP
```

### `customers`
```sql
id                  UUID PRIMARY KEY
phone               VARCHAR(20) UNIQUE NOT NULL  -- E.164 format
reservations_count  INT DEFAULT 0
shows_count         INT DEFAULT 0
no_shows_count      INT DEFAULT 0
reliability_score   DECIMAL(5,2) NULL  -- NULL = no history
last_calculated_at  TIMESTAMP
created_at          TIMESTAMP
```

### `reservations`
```sql
id              UUID PRIMARY KEY
business_id     UUID FK businesses
customer_id     UUID FK customers
customer_name   VARCHAR(255) NOT NULL
date            DATE NOT NULL
time            TIME NOT NULL
guests          SMALLINT DEFAULT 1
notes           TEXT
status          ENUM('pending_verification', 'pending_reminder', 'confirmed',
                     'cancelled_by_client', 'cancelled_no_confirmation',
                     'no_show', 'show') DEFAULT 'pending_verification'
phone_verified  BOOLEAN DEFAULT FALSE  -- true if confirmed by phone call
confirmation_token  UUID UNIQUE
token_expires_at    TIMESTAMP
reminder_2h_sent    BOOLEAN DEFAULT FALSE
reminder_30m_sent   BOOLEAN DEFAULT FALSE
created_at      TIMESTAMP
updated_at      TIMESTAMP
```

### `sms_logs`
```sql
id              UUID PRIMARY KEY
reservation_id  UUID FK reservations
phone           VARCHAR(20) NOT NULL
type            ENUM('verification', 'reminder_2h', 'reminder_30m', 'waitlist')
provider_id     VARCHAR(100)  -- Twilio SID
status          ENUM('queued', 'sent', 'delivered', 'failed')
cost_eur        DECIMAL(8,4)
error_message   TEXT
sent_at         TIMESTAMP
delivered_at    TIMESTAMP
created_at      TIMESTAMP
```

---

## 10. Security & Configuration

### Authentication
- Business accounts: Laravel Sanctum SPA tokens
- Client confirmation: tokenized single-use UUID links (no auth required)
- Webhook endpoint (Twilio): HMAC signature validation on every request

### Data Handling
- Phone numbers stored in E.164 format
- Reliability scores are aggregated behavioral data tied to phone numbers — treated as personal data under GDPR
- SMS logs retained for 90 days, then purged (configurable)
- Confirmation tokens single-use and time-limited
- All data encrypted at rest (PostgreSQL encryption) and in transit (TLS 1.3)

### Configuration
- All API keys (Twilio, etc.) via environment variables — never in code
- `.env` not committed to repository
- Separate `.env` per environment (local, staging, production)

### GDPR Scope
- Data collected: phone number, appointment behavior (show/no-show)
- Lawful basis: legitimate interest (fraud/reliability prevention) — legal review recommended before scaling beyond France
- Data deletion: businesses can delete a customer record on request, which removes the phone number and anonymizes historical scores
- **Pen testing, SOC2, and formal GDPR audit are out of scope for MVP**

---

## 11. API Specification

### Authentication
All endpoints (except webhook and confirmation) require `Authorization: Bearer {token}`.

---

```
POST /api/auth/register
Auth: None
Request:  { name, email, password, business_name, phone }
Response: { token, business, trial_ends_at }
Errors:   422 (validation), 409 (email exists)
```

```
POST /api/auth/login
Auth: None
Request:  { email, password }
Response: { token, business }
Errors:   401 (invalid credentials)
```

```
POST /api/reservations
Auth: Bearer
Request:  { customer_name, phone, date, time, guests?, notes?, phone_verified? }
Response: { reservation, customer.reliability_score }
Errors:   422 (validation), 402 (trial expired, no active subscription)
```

```
GET /api/reservations
Auth: Bearer
Query:    ?date=2026-03-12 | ?week=2026-W11
Response: { reservations: [...], stats: { confirmed, pending, cancelled, no_show } }
```

```
GET /api/reservations/{id}
Auth: Bearer
Response: { reservation, customer, sms_logs }
Errors:   404
```

```
PATCH /api/reservations/{id}/status
Auth: Bearer
Request:  { status: 'show' | 'no_show' }
Response: { reservation, customer.reliability_score }
Errors:   422 (invalid transition)
```

```
GET /api/c/{token}
Auth: None (client confirmation page)
Response: HTML page (Blade/Vue)
Errors:   404 (invalid token), 410 (expired or already used)
```

```
POST /api/c/{token}/confirm
Auth: None
Request:  { action: 'confirm' | 'cancel' }
Response: { message, reservation.status }
Errors:   410 (expired/used), 422
```

```
POST /api/webhooks/twilio
Auth: HMAC signature (X-Twilio-Signature header)
Request:  Twilio delivery status payload
Response: 200 OK
Errors:   403 (invalid signature)
```

```
GET /api/dashboard
Auth: Bearer
Response: { today_stats, weekly_no_show_rate, sms_cost_this_month }
```

---

## 12. Success Criteria

### MVP Definition of Done
The MVP is shippable when a business can:
1. Create an account and send their first SMS in < 5 minutes
2. See all their day's reservations and their statuses on the dashboard
3. Have the system automatically send reminders without any manual action

### Functional Requirements
- [ ] Account creation and login work end-to-end
- [ ] Reservation creation triggers SMS within 30 seconds
- [ ] Phone-verified flow skips verification SMS correctly
- [ ] Confirmation link updates status and reliability score
- [ ] Scheduler triggers reminders at correct times (2h, 30min) based on score tier
- [ ] Auto-cancellation fires when confirmation deadline passes
- [ ] Reliability score updates within 5 minutes of status change
- [ ] Dashboard shows real-time reservation statuses
- [ ] SMS logs visible per reservation (type, status, cost)
- [ ] Trial and subscription states enforced (trial block after 14 days)
- [ ] Twilio webhook updates SMS delivery status

### Quality Indicators
- Dashboard loads in < 1 second for up to 100 reservations/day
- Reminder SMS delivered within 5 minutes of scheduled trigger time
- SMS queue processes jobs within 30 seconds under normal load
- Zero silent failures: all SMS errors logged with detail

### UX Goals
- Business can create a reservation without reading documentation
- Client confirmation requires 0 accounts and ≤ 2 taps

---

## 13. Implementation Phases

### Phase 1 — Foundation (Week 1–2)
**Goal:** Working auth, reservation model, and SMS pipeline.

- [ ] Laravel project setup (PostgreSQL, Redis, queues, Sanctum)
- [ ] Authentication: register, login, logout
- [ ] Database migrations: businesses, customers, reservations, sms_logs
- [ ] ReservationController: create, list, show, update status
- [ ] SmsService abstraction (Twilio integration)
- [ ] SendVerificationSms job
- [ ] Confirmation endpoint (GET + POST `/api/c/{token}`)
- [ ] Basic Vue.js SPA scaffold with auth

**Validation:** A business can create a reservation and the client receives a real SMS with a working confirmation link.

---

### Phase 2 — Smart Reminders & Scoring (Week 3)
**Goal:** Fully automated reminder pipeline and reliability score.

- [ ] ReliabilityScoreService + RecalculateReliabilityScore job
- [ ] SendReminderSms job (2h and 30min variants)
- [ ] Laravel Scheduler: reminder trigger command (runs every minute)
- [ ] Auto-cancellation logic (unconfirmed past deadline)
- [ ] Phone-verified flow (skip verification SMS)
- [ ] Score display in reservation form and list
- [ ] Twilio webhook for delivery status

**Validation:** End-to-end flow with real SMS: create reservation → reminder fires at right time based on score → auto-cancel if no response.

---

### Phase 3 — Dashboard & Billing (Week 4)
**Goal:** Complete dashboard and trial/subscription enforcement.

- [ ] Dashboard view (today/week, stats, statuses)
- [ ] Color-coded status badges and reliability score badges
- [ ] Manual No-Show / Show marking with undo (30min window)
- [ ] SMS cost tracking and display (per reservation + monthly total)
- [ ] Trial enforcement: block reservation creation after 14 days if no subscription
- [ ] Subscription activation flow (Stripe)
- [ ] Trial expiry email notification (48h before)

**Validation:** Business can manage a full day of reservations entirely from the dashboard.

---

### Phase 4 — Hardening & Launch (Week 5–6)
**Goal:** Production-ready, secure, and documented for first customers.

- [ ] Twilio webhook HMAC validation
- [ ] Input validation and error handling on all endpoints
- [ ] Queue failure alerting (failed jobs after 3 retries)
- [ ] Rate limiting on public endpoints (confirmation, webhook)
- [ ] Edge cases: appointment < 2h away, link expired, duplicate confirmation
- [ ] Smoke tests for critical paths (reservation → SMS → confirm → score)
- [ ] Production deployment (server TBD)
- [ ] Onboarding flow tested with 3 real businesses (prospection locale)

**Validation:** 3 external businesses use ZeroNoShow in real conditions for 1 week without issues.

---

## 14. Risks & Mitigations

| Risk | Likelihood | Impact | Mitigation |
|---|---|---|---|
| SMS costs exceed projection for heavy users | Medium | High | Pay-per-SMS model shifts cost to client; display real-time cost in dashboard |
| Twilio SMS delivery failures in France | Low | High | Implement delivery status tracking via webhook; retry failed sends once; alert business |
| Reliability score sparse for new businesses (cold start) | High | Medium | Treat unknown phones as At Risk (most reminders); score improves naturally with usage |
| GDPR challenge on cross-business phone scoring | Medium | High | Anonymize on deletion request; add opt-out link to first SMS; consult legal before scaling |
| Trial abuse (create new accounts to extend trial) | Low | Low | One trial per email address; flag accounts with same business phone |
| Scheduler downtime causes missed reminders | Low | High | Monitor scheduler health; missed reminders logged and alerted; manual retry from dashboard |

---

## 15. Future Considerations

### V2 Features (Post-MVP)
- **Waitlist system**: notify waitlisted clients when a slot frees up (first YES wins)
- **WhatsApp / Telegram channel**: ask during phone booking, use as primary channel if available (~10x cheaper than SMS)
- **Third-party integrations**: TheFork, Doctolib, Planity, Calendly via webhooks — auto-import reservations, eliminate manual entry
- **Zapier / Make connectors**: enable integrations with any booking tool via no-code

### V3 Features
- **Advanced analytics**: no-show rate by day/time/client segment, revenue recovered estimate
- **Multi-location**: one account for multi-branch businesses
- **AI no-show prediction**: model trained on platform data to predict show probability beyond simple scoring
- **Multi-language support**: English, Spanish, Italian

### Open Questions
1. **Hosting provider?** — TBD (Laravel Forge + DigitalOcean recommended)
2. **Legal review on cross-business reliability scoring** — Recommended before reaching 50+ businesses

---

## Appendix

### Pricing Summary
| Plan | Price | Includes |
|---|---|---|
| Trial | Free (14 days) | Full access, SMS at cost |
| Standard | 19€/month | Unlimited reservations, SMS at cost |
| SMS (France) | ~0.07€/SMS | Billed separately per send |

### Key SMS Flows
| Trigger | SMS count | When |
|---|---|---|
| New reservation (unverified number) | 1 verification | Immediately |
| Reliable client (>90%) | 0 reminders | — |
| Average client (70–90%) | 1 reminder | 2h before |
| At Risk client (<70%) | 2 reminders | 2h + 30min before |
| Waitlist slot available (V2) | 1 notification | On slot opening |
