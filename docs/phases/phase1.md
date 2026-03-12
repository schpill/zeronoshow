# Phase 1 — Foundation

| Field            | Value                                                                 |
|------------------|-----------------------------------------------------------------------|
| **Phase**        | 1 of 4                                                                |
| **Name**         | Foundation — Auth, Reservations & SMS Pipeline                        |
| **Duration**     | Weeks 1–2 (2 weeks)                                                   |
| **Milestone**    | M1 — A business can create a reservation and a client receives a real SMS with a working confirmation link |
| **PRD Sections** | §4 (MVP Scope), §5 (US-01, US-02, US-05, US-08), §7 (Features 1, 4), §9 (DB Schema), §10 (Security), §11 (API) |
| **Prerequisite** | None                                                                  |
| **Status**       | Not started                                                           |

---

## Section 1 — Phase Objectives

| ID       | Objective                                                                                          | Verifiable?          |
|----------|----------------------------------------------------------------------------------------------------|----------------------|
| P1-OBJ-1 | Business can register an account and log in via the Vue.js SPA                                     | E2E test passes      |
| P1-OBJ-2 | Business can create a reservation (unverified number) and a verification SMS is dispatched within 30 seconds | Feature test passes  |
| P1-OBJ-3 | Business can create a reservation with phone_verified=true and no verification SMS is sent         | Feature test passes  |
| P1-OBJ-4 | Client can confirm or cancel via the tokenized link; reservation status updates in DB              | Feature test passes  |
| P1-OBJ-5 | All four DB tables (businesses, customers, reservations, sms_logs) exist with correct schema       | Migration runs clean |
| P1-OBJ-6 | All SMS sends go through the queue (never synchronous); failed jobs retry 3× with backoff         | Unit test passes     |
| P1-OBJ-7 | Docker Compose stack runs locally with a single `docker compose up`                                | Manual verification  |
| P1-OBJ-8 | CI pipeline (lint + test) runs on every push to `main`                                             | GitHub Actions green |

---

## Section 2 — Entry Criteria

- Twilio account created, Account SID + Auth Token + From number available
- Stripe account created (test mode keys available, not used in Phase 1 but `.env.example` must reference them)
- GitHub repository created and accessible
- Docker Desktop installed on dev machine
- PHP 8.3+ and Composer 2 available locally
- Node.js 20+ and pnpm@10 available locally (`npm install -g pnpm@10`)

---

## Section 3 — Scope — Requirement Traceability

| PRD Requirement Group                     | IDs in PRD       | Status   | Notes                                                    |
|-------------------------------------------|------------------|----------|----------------------------------------------------------|
| Business registration + authentication    | US-07, §10 Auth  | Included | Full register/login/logout                               |
| Reservation creation (unverified)         | US-01, US-02, Feature 1 | Included | With verification SMS dispatch                  |
| Reservation creation (phone-verified)     | US-01, Feature 1 | Included | Skips verification SMS, schedules reminder slot          |
| Client confirmation flow                  | US-05, Feature 4 | Included | Token-based, no account                                  |
| Client cancel flow                        | US-05, Feature 4 | Included | Via same confirmation page                               |
| SMS queuing + retry                       | US-08            | Included | Queue-based, 3 retries, exponential backoff              |
| Reliability score display                 | US-04, Feature 2 | Partial  | Score READ on reservation creation; score WRITE deferred to Phase 2 |
| Smart reminders                           | US-03, Feature 3 | No       | Deferred to Phase 2 (scheduler not built yet)            |
| Auto-cancellation                         | Feature 3        | No       | Deferred to Phase 2                                      |
| Twilio delivery webhook                   | US-08, §11       | Partial  | Route + controller stub created; full implementation in Phase 2 |
| Dashboard UI                              | US-06, Feature 5 | Partial  | Vue SPA scaffold + auth pages only; full dashboard in Phase 3 |
| Subscription / billing                   | US-07, §7 Billing | No      | Deferred to Phase 3                                      |

---

## Design System — Références Obligatoires

> **IMPORTANT** — Tout développement frontend DOIT se conformer aux documents de design ci-dessous. Ces fichiers font autorité sur toute décision visuelle. Aucune couleur, police ou style ne peut être introduit sans que sa source soit identifiable dans ces références.

| Document | Chemin | Contenu | Quand le consulter |
|----------|--------|---------|-------------------|
| **Charte des couleurs** | `docs/graphics/colors.md` | Palette Emerald + Slate, couleurs sémantiques (succès/erreur/avertissement/info), 7 statuts de réservation, 3 tiers de fiabilité — avec classes Tailwind exactes | Avant tout composant utilisant couleurs, badges ou indicateurs d'état |
| **Système typographique** | `docs/graphics/polices.md` | Inter (sans-serif) + JetBrains Mono, classes utilitaires `.text-heading-*`, `.text-body-*`, `.text-label`, `.text-badge`, `.text-caption`, `.text-overline` | Avant tout composant affichant du texte |
| **Logo mode clair** | `docs/assets/logos/zeronoshow-light.svg` | Logo officiel sur fond clair (texte slate-800) | Header en mode clair, pages publiques, emails |
| **Logo mode sombre** | `docs/assets/logos/zeronoshow-dark.svg` | Logo officiel sur fond sombre (texte white) | Header en mode sombre, fonds colorés |
| **Icône / Favicon** | `docs/assets/logos/zeronoshow-icon.svg` | Symbole seul sans texte | Favicon, app icon, espaces réduits |
| **Template backoffice client** | `docs/graphics/templates/template_backoffice_client.html` | Layout complet : sidebar w-60, header h-16, stats bar 4 colonnes, tableau réservations (7 badges statut + 3 badges fiabilité), formulaire rapide, synthèse mensuelle, **dark mode complet** avec mappings `dark:` exacts | Toutes les pages du backoffice client |
| **Template backoffice admin** | `docs/graphics/templates/template_backoffice_zeronoshow.html` | Espace admin ZNS : sidebar slate-900, accent violet, tableau établissements, health panel, graphique SMS, logs d'activité | Référence pour interface interne ZeroNoShow |
| **Template site vitrine** | `docs/graphics/templates/template_site_vitrine.html` | Landing page : hero, features, pricing, CTA — palette Emerald sur blanc | Pages publiques, page confirmation client `/c/{token}` |

### Règles impératives

1. **Aucune couleur hors charte** — utiliser exclusivement les classes Tailwind définies dans `colors.md`. Emerald pour les actions principales, Slate pour fonds/textes neutres, couleurs sémantiques pour les états.
2. **Dark mode dès le départ** — chaque composant Vue inclut ses variantes `dark:` dès le premier commit. Mappings de référence dans `template_backoffice_client.html` (`dark:bg-slate-950` page, `dark:bg-slate-900` cards/sidebar, `dark:border-slate-800` bordures, `dark:text-slate-50` titres).
3. **Polices via Google Fonts** — Inter + JetBrains Mono chargées via preconnect dans `index.html`. Utiliser exclusivement les classes utilitaires de `polices.md`, jamais de `font-size` ou `font-weight` Tailwind bruts.
4. **Logo** — toujours `zeronoshow-light.svg` sur fond clair, `zeronoshow-dark.svg` sur fond sombre. Ne jamais modifier les fichiers SVG sources.
5. **Templates HTML = spec visuelle de facto** — le rendu des templates `.html` constitue la référence pixel-perfect. Toute divergence doit être justifiée et validée avant implémentation.

---

## Section 4 — Sprint Breakdown

### 4.1 Sprint 1 — Foundation (Weeks 1–2)

#### 4.1.1 Sprint Objectives

- Laravel 12 project boots with PostgreSQL + Redis + queue worker via Docker Compose
- All four DB migrations run cleanly on a fresh database
- Business can register, log in, receive a Sanctum token, and log out
- Business can create a reservation; system resolves or creates the Customer record by phone
- Unverified flow: `SendVerificationSms` job dispatched → Twilio SDK called → SMS sent to real phone
- Phone-verified flow: no SMS sent, reservation status set to `pending_reminder`
- Client opens `/c/{token}` → sees appointment details → confirms or cancels → DB updated
- Vue SPA scaffold renders Login and Register pages, handles auth state via Pinia
- GitHub Actions CI runs `php artisan test` and `npm run lint` on every push

---

#### 4.1.2 Database Migrations

| Migration name                        | Description                                                                                                                                                                                                                                                                                                                                                                                                                                                                          |
|---------------------------------------|--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `2026_03_12_000001_create_businesses_table` | id UUID PK DEFAULT gen_random_uuid(), name VARCHAR(255) NOT NULL, email VARCHAR(255) UNIQUE NOT NULL, password VARCHAR(255) NOT NULL, phone VARCHAR(20) NULLABLE, timezone VARCHAR(50) NOT NULL DEFAULT 'Europe/Paris', subscription_status ENUM('trial','active','cancelled') NOT NULL DEFAULT 'trial', trial_ends_at TIMESTAMPTZ NOT NULL, stripe_customer_id VARCHAR(100) NULLABLE, stripe_subscription_id VARCHAR(100) NULLABLE, remember_token VARCHAR(100) NULLABLE, created_at TIMESTAMPTZ, updated_at TIMESTAMPTZ. Indexes: email UNIQUE (btree). |
| `2026_03_12_000002_create_customers_table`  | id UUID PK DEFAULT gen_random_uuid(), phone VARCHAR(20) UNIQUE NOT NULL (E.164 format), reservations_count INT NOT NULL DEFAULT 0, shows_count INT NOT NULL DEFAULT 0, no_shows_count INT NOT NULL DEFAULT 0, reliability_score DECIMAL(5,2) NULLABLE (NULL = no history), last_calculated_at TIMESTAMPTZ NULLABLE, created_at TIMESTAMPTZ, updated_at TIMESTAMPTZ. Indexes: phone UNIQUE (btree).                                                                                  |
| `2026_03_12_000003_create_reservations_table` | id UUID PK DEFAULT gen_random_uuid(), business_id UUID NOT NULL FK→businesses(id) ON DELETE CASCADE, customer_id UUID NOT NULL FK→customers(id) ON DELETE RESTRICT, customer_name VARCHAR(255) NOT NULL, scheduled_at TIMESTAMPTZ NOT NULL (date+time merged, stored UTC), guests SMALLINT NOT NULL DEFAULT 1, notes TEXT NULLABLE, status ENUM('pending_verification','pending_reminder','confirmed','cancelled_by_client','cancelled_no_confirmation','no_show','show') NOT NULL DEFAULT 'pending_verification', phone_verified BOOLEAN NOT NULL DEFAULT false, confirmation_token UUID UNIQUE NULLABLE, token_expires_at TIMESTAMPTZ NULLABLE, reminder_2h_sent BOOLEAN NOT NULL DEFAULT false, reminder_30m_sent BOOLEAN NOT NULL DEFAULT false, status_changed_at TIMESTAMPTZ NULLABLE, created_at TIMESTAMPTZ, updated_at TIMESTAMPTZ. Indexes: (business_id, scheduled_at) composite btree; (status, reminder_2h_sent, reminder_30m_sent, scheduled_at) composite btree for scheduler queries; confirmation_token UNIQUE (btree). |
| `2026_03_12_000004_create_sms_logs_table`    | id UUID PK DEFAULT gen_random_uuid(), reservation_id UUID NOT NULL FK→reservations(id) ON DELETE CASCADE, business_id UUID NOT NULL FK→businesses(id) ON DELETE CASCADE, phone VARCHAR(20) NOT NULL, type ENUM('verification','reminder_2h','reminder_30m') NOT NULL, body TEXT NOT NULL (SMS content sent), twilio_sid VARCHAR(100) NULLABLE, status ENUM('queued','sent','delivered','failed') NOT NULL DEFAULT 'queued', cost_eur DECIMAL(8,4) NULLABLE, error_message TEXT NULLABLE, queued_at TIMESTAMPTZ NOT NULL, sent_at TIMESTAMPTZ NULLABLE, delivered_at TIMESTAMPTZ NULLABLE, created_at TIMESTAMPTZ. Indexes: (business_id, created_at) composite btree; reservation_id (btree); twilio_sid (btree, for webhook lookup).                                    |

---

#### 4.1.3 Back-end Tasks

| ID         | Task                                                                                                                                                                                                                                                                                                     | PRD Ref          |
|------------|----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|------------------|
| P1-BE-001  | Create Laravel 12 project via `composer create-project laravel/laravel zeronoshow`. Configure `composer.json` to require PHP 8.3+. Set `APP_NAME=ZeroNoShow`, `APP_URL`, `APP_TIMEZONE=UTC` in `.env.example`.                                                                                            | §8 Tech Stack    |
| P1-BE-002  | Install Laravel Sanctum: `composer require laravel/sanctum`. Publish config (`php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"`). Configure `config/sanctum.php`: token expiry 30 days (`expiration: 43200`). Add `HasApiTokens` to `Business` model (Phase 1-BE-010).     | §10 Auth, §11    |
| P1-BE-003  | Install Twilio SDK: `composer require twilio/sdk`. Add env vars to `.env.example`: `TWILIO_ACCOUNT_SID`, `TWILIO_AUTH_TOKEN`, `TWILIO_FROM` (E.164 format), `TWILIO_WEBHOOK_SECRET`. Do not bind service yet (done in P1-BE-016).                                                                        | §8 SMS Provider  |
| P1-BE-004  | Configure PostgreSQL connection in `config/database.php`: driver pgsql, charset utf8, use `DB_*` env vars. Configure Redis connection: driver redis, host `REDIS_HOST`, port 6379, used for `CACHE_DRIVER=redis` and `QUEUE_CONNECTION=redis`. Add all vars to `.env.example`.                            | §8 DB, §6 Infra  |
| P1-BE-005  | Create `app/Http/Middleware/RequireActiveSubscription.php`. Logic: check `auth()->user()->subscription_status` is `trial` (and `trial_ends_at > now()`) or `active`. Return 402 JSON error `{"error":{"code":"SUBSCRIPTION_REQUIRED","message":"Your trial has expired. Please subscribe to continue."}}` if not. Register as `subscription` alias in `bootstrap/app.php`.  | §10, US-07       |
| P1-BE-006  | Write all four DB migrations (see §4.1.2 above). Use `Str::uuid()` for UUID defaults in seeders. Enable `uuid-ossp` PostgreSQL extension in migration `P1-BE-006b`: `DB::statement('CREATE EXTENSION IF NOT EXISTS "pgcrypto"')` for `gen_random_uuid()`. Confirm migrations run cleanly: `php artisan migrate:fresh`.  | §9 DB Schema     |
| P1-BE-007  | Create `app/Models/Business.php`. Extend `Authenticatable`. Traits: `HasApiTokens`, `HasFactory`. `$fillable`: `name`, `email`, `password`, `phone`, `timezone`, `subscription_status`, `trial_ends_at`, `stripe_customer_id`, `stripe_subscription_id`. `$hidden`: `password`, `remember_token`. `$casts`: `trial_ends_at → datetime`, `subscription_status → string`. Relationship: `hasMany(Reservation::class)`. Accessor `isOnActivePlan(): bool` returns `subscription_status === 'active' \|\| ($subscription_status === 'trial' && trial_ends_at->isFuture())`. | §9, §10          |
| P1-BE-008  | Create `app/Models/Customer.php`. `$fillable`: `phone`, `reservations_count`, `shows_count`, `no_shows_count`, `reliability_score`, `last_calculated_at`. `$casts`: `reliability_score → float`, `last_calculated_at → datetime`. Relationship: `hasMany(Reservation::class)`. Method `getScoreTier(): string` returns `'reliable'` if score ≥ 90, `'average'` if 70–89, `'at_risk'` if < 70 or null. | §9, Feature 2    |
| P1-BE-009  | Create `app/Models/Reservation.php`. `$fillable`: all columns except id, timestamps. `$casts`: `scheduled_at → datetime`, `phone_verified → boolean`, `reminder_2h_sent → boolean`, `reminder_30m_sent → boolean`, `status_changed_at → datetime`, `token_expires_at → datetime`. Relationships: `belongsTo(Business::class)`, `belongsTo(Customer::class)`, `hasMany(SmsLog::class)`. Scope `scopeNeedingReminder(Builder $query)`: status IN pending_reminder/confirmed AND reminder_2h_sent=false AND scheduled_at between now+1h55m and now+2h05m.  | §9, Feature 1    |
| P1-BE-010  | Create `app/Models/SmsLog.php`. `$fillable`: all columns. `$casts`: `queued_at → datetime`, `sent_at → datetime`, `delivered_at → datetime`, `cost_eur → float`. Relationships: `belongsTo(Reservation::class)`, `belongsTo(Business::class)`.                                                           | §9               |
| P1-BE-011  | Create `app/Services/Contracts/SmsServiceInterface.php`. Define methods: `send(string $to, string $body): SmsLog` (dispatches job, returns created SmsLog record). `validateWebhookSignature(Request $request): bool`.                                                                                    | §6.3 Architecture|
| P1-BE-012  | Create `app/Services/TwilioSmsService.php` implementing `SmsServiceInterface`. `send()`: instantiate `Twilio\Rest\Client` with `TWILIO_ACCOUNT_SID` + `TWILIO_AUTH_TOKEN`. Call `$client->messages->create($to, ['from' => config('services.twilio.from'), 'body' => $body])`. Store `twilio_sid` + status `sent` in the `SmsLog` passed in. Throw `SmsDeliveryException` on Twilio RestException. `validateWebhookSignature()`: use `Twilio\Security\RequestValidator::validate()` with `TWILIO_AUTH_TOKEN`, request URL, and POST params.  | §7.1 SmsService  |
| P1-BE-013  | Bind `SmsServiceInterface` → `TwilioSmsService` as singleton in `app/Providers/AppServiceProvider.php`: `$this->app->singleton(SmsServiceInterface::class, TwilioSmsService::class)`. Also register `config/services.php` entry for twilio: `['sid' => env('TWILIO_ACCOUNT_SID'), 'token' => env('TWILIO_AUTH_TOKEN'), 'from' => env('TWILIO_FROM')]`.  | §6.3 Architecture|
| P1-BE-014  | Create `app/Http/Requests/Auth/RegisterRequest.php`. Rules: `name` (required, string, max 255), `email` (required, email:rfc,dns, max 255, unique:businesses,email), `password` (required, string, min 8, confirmed), `business_name` (required, string, max 255), `phone` (required, string, regex E.164 `/^\+[1-9]\d{7,14}$/`). Custom messages in French.                                                                                                                       | US-07, §11       |
| P1-BE-015  | Create `app/Http/Controllers/Auth/AuthController.php`. Method `register(RegisterRequest $request): JsonResponse`: create Business with `business_name → name`, hash password, `trial_ends_at = now()->addDays(14)`, `subscription_status = 'trial'`. Create Sanctum token named `'web'`. Return 201 `{token, business: {id, name, email, trial_ends_at, subscription_status}}`. Method `login(Request $request): JsonResponse`: validate email+password, check credentials via `Auth::attempt`, create token, return 200 same shape. Method `logout(Request $request): JsonResponse`: `$request->user()->currentAccessToken()->delete()`, return 204.  | US-07, §11       |
| P1-BE-016  | Create `app/Http/Requests/StoreReservationRequest.php`. Rules: `customer_name` (required, string, max 255), `phone` (required, string, regex E.164), `scheduled_at` (required, date, after:now), `guests` (nullable, integer, min 1, max 100), `notes` (nullable, string, max 1000), `phone_verified` (nullable, boolean). Custom messages. Edge case: if `scheduled_at` is in the past by more than 5 minutes → fail validation `"Appointment date must be in the future"`.                                                                                                   | US-01, Feature 1 |
| P1-BE-017  | Create `app/Http/Controllers/ReservationController.php`. Method `store(StoreReservationRequest $request): JsonResponse`: (1) `firstOrCreate` Customer by phone (increment `reservations_count`). (2) Determine token expiry: `min(now()->addHours(24), Carbon::parse($scheduled_at)->subHours(2))`. If `scheduled_at` < 30min away: status `pending_verification` but do NOT generate token, mark `too_late_to_confirm` (add status or handle via notes). (3) Create Reservation. (4) If NOT `phone_verified`: dispatch `SendVerificationSms::dispatch($reservation)`. If `phone_verified`: set status `pending_reminder`. (5) Return 201 `{reservation, customer: {reliability_score, score_tier}}`. | US-01, US-02, Feature 1 |
| P1-BE-018  | Method `ReservationController::index(Request $request): JsonResponse`. Auth: business. Query params: `date` (ISO date, default today) OR `week` (ISO week e.g. `2026-W11`). Filter by `business_id` + `date(scheduled_at)` for daily, or `EXTRACT(week FROM scheduled_at)` for weekly. Return `{reservations: ReservationResource[], stats: {confirmed, pending_verification, pending_reminder, cancelled, no_show, show, total}}`. Cached in Redis key `dashboard:{business_id}:{date}` TTL 30 seconds.                                                                         | US-06, §11       |
| P1-BE-019  | Method `ReservationController::show(Reservation $reservation): JsonResponse`. Auth: business. Policy check: `$reservation->business_id === auth()->id()` → 403 if mismatch. Return `{reservation, customer: {phone, reliability_score, score_tier, reservations_count, shows_count, no_shows_count}, sms_logs: SmsLogResource[]}`.                                                                                                                                                   | §11              |
| P1-BE-020  | Create `app/Http/Resources/ReservationResource.php`. Fields: `id, customer_name, scheduled_at (ISO 8601), guests, notes, status, phone_verified, reminder_2h_sent, reminder_30m_sent, created_at`. Include `customer` as nested resource when loaded. Include `sms_logs` count as `sms_count`.                                                                                                                                                                                   | §11              |
| P1-BE-021  | Create `app/Jobs/SendVerificationSms.php`. Implements `ShouldQueue`. `$tries = 3`, `backoff = [60, 300, 900]` (1min, 5min, 15min). `handle(SmsServiceInterface $sms)`: (1) Re-fetch Reservation from DB (may have been confirmed already — abort if status !== `pending_verification`). (2) Build SMS body: `"Bonjour {name}, confirmez votre RDV le {date} à {time}. Confirmez : {url} | Annulez : {cancel_url}"`. (3) Call `$sms->send($reservation->customer->phone, $body)`. (4) Create `SmsLog` record with type `verification`, status `queued`, queued_at now. `failed()`: update SmsLog status to `failed`, log error to Sentry.                                              | US-02, US-08     |
| P1-BE-022  | Create `app/Http/Controllers/ConfirmationController.php`. Method `show(string $token): View\|Response`: find Reservation by `confirmation_token = $token`. If not found → 404 view "Lien invalide". If `token_expires_at < now()` → 410 view "Ce lien a expiré". If status already confirmed/cancelled → 410 view "Vous avez déjà répondu". Return Blade view `confirmation.show` with `{business_name, customer_name, scheduled_at, guests}`.                                                | US-05, Feature 4 |
| P1-BE-023  | Method `ConfirmationController::confirm(Request $request, string $token): JsonResponse\|Response`: validate `action` (required, in:confirm,cancel). Find Reservation by token (same checks as `show`). Wrap in DB transaction: (1) Map action → status: confirm→`confirmed`, cancel→`cancelled_by_client`. (2) Update `reservation.status`, `status_changed_at = now()`, invalidate token (`confirmation_token = null`, `token_expires_at = null`). (3) Dispatch `RecalculateReliabilityScore::dispatch($reservation->customer_id)` (stub job in Phase 1). Return Blade view with success message. Edge case: if called twice within 30-min window, last action wins (re-process normally).  | US-05, Feature 4 |
| P1-BE-024  | Create stub `app/Jobs/RecalculateReliabilityScore.php` (full implementation in Phase 2). Job accepts `customerId: string`. `handle()`: log "Score recalculation queued for {customerId}" — no-op for now.                                                                                                 | US-04, Feature 2 |
| P1-BE-025  | Create stub `app/Http/Controllers/Webhook/TwilioWebhookController.php`. Method `handle(Request $request): Response`: log entire payload to Laravel log channel. Return `response('', 200)`. Full HMAC validation + processing in Phase 2.                                                               | US-08, §11       |
| P1-BE-026  | Create `app/Http/Controllers/Api/CustomerController.php`. Method `lookup(Request $request): JsonResponse`: validate `phone` (required, E.164 regex). Find Customer by phone. Return `{found: bool, reliability_score: float\|null, score_tier: string\|null}`. Used by Vue form to show score on phone entry.  | US-04, §11       |
| P1-BE-027  | Configure `routes/api.php` with versioned prefix `/api/v1`. Auth routes (no middleware): `POST /auth/register`, `POST /auth/login`. Authenticated routes (`auth:sanctum` + `subscription` middleware): `POST /reservations`, `GET /reservations`, `GET /reservations/{reservation}`, `GET /customers/lookup`. Auth-only (no subscription gate): `POST /auth/logout`. Public routes (no middleware): `GET /c/{token}` and `POST /c/{token}/confirm` in `routes/web.php`. Webhook: `POST /api/v1/webhooks/twilio` (no Sanctum, raw route).                                              | §11, §6.3        |
| P1-BE-028  | Create Blade views for confirmation flow. `resources/views/confirmation/show.blade.php`: minimal mobile-first HTML (no JS framework), shows business name, client name, date/time, guests, two buttons (Confirm / Cancel) that POST to `/c/{token}/confirm`. `resources/views/confirmation/result.blade.php`: shows success/error message. No Tailwind build step — use CDN Tailwind for these pages only.  | US-05, Feature 4 |

---

#### 4.1.4 Back-end Tests (TDD)

| Test File                                          | Test Cases                                                                                                                                                                                                                    |
|----------------------------------------------------|-------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `tests/Feature/Auth/RegisterTest.php`              | registers with valid data and returns token; fails with duplicate email (409); fails with invalid email format (422); fails with phone not E.164 (422); fails with password_confirmation mismatch (422); trial_ends_at is 14 days from now |
| `tests/Feature/Auth/LoginTest.php`                 | returns token with valid credentials; fails with wrong password (401); fails with unknown email (401); previous tokens remain valid after new login                                                                            |
| `tests/Feature/Auth/LogoutTest.php`                | deletes current token on logout; returns 401 on subsequent authenticated request with same token                                                                                                                              |
| `tests/Feature/Reservation/StoreReservationTest.php` | creates reservation and dispatches SendVerificationSms for unknown number; creates reservation with phone_verified=true → status pending_reminder, no job dispatched; returns reliability_score from existing customer; fails with past scheduled_at; fails with invalid E.164 phone; finds or creates customer by phone; returns 402 when subscription expired |
| `tests/Feature/Reservation/ShowReservationTest.php` | returns reservation with customer and sms_logs; returns 403 if reservation belongs to another business; returns 404 for unknown id |
| `tests/Feature/Reservation/IndexReservationTest.php` | returns reservations filtered by date; returns correct stats counts; returns empty list for date with no reservations; week filter returns 7 days of reservations |
| `tests/Feature/Confirmation/ShowConfirmationTest.php` | shows confirmation page for valid token; returns 404 for unknown token; returns 410 for expired token; returns 410 for already-confirmed reservation |
| `tests/Feature/Confirmation/ConfirmActionTest.php` | confirms reservation and updates status to confirmed; cancels reservation and updates status to cancelled_by_client; invalidates token after action; dispatches RecalculateReliabilityScore job; fails with invalid action value; returns 410 for expired token |
| `tests/Unit/Models/CustomerTest.php`               | getScoreTier returns reliable for score ≥ 90; returns average for score 70–89; returns at_risk for score < 70; returns at_risk for null score |
| `tests/Unit/Models/BusinessTest.php`               | isOnActivePlan returns true for active status; true for trial with future trial_ends_at; false for trial with past trial_ends_at; false for cancelled |
| `tests/Unit/Jobs/SendVerificationSmsTest.php`      | sends SMS via SmsServiceInterface; aborts if reservation status is no longer pending_verification; creates SmsLog with type verification; marks SmsLog failed on Twilio exception; retries 3 times with correct backoff |

---

#### 4.1.5 Front-end Tasks

> **Référence design obligatoire** — Toutes les tâches frontend doivent strictement respecter :
> - **Couleurs** : `docs/graphics/colors.md` — palette Emerald + Slate uniquement, aucune couleur hors charte
> - **Typographie** : `docs/graphics/polices.md` — Inter (sans), JetBrains Mono (mono), classes utilitaires `.text-heading-*`, `.text-label`, `.text-caption`, `.text-overline`
> - **Templates de référence** : `docs/graphics/templates/template_backoffice_client.html` (backoffice client), `docs/graphics/templates/template_site_vitrine.html` (landing page)
> - **Dark mode** : toujours prévoir les variantes `dark:` dès la Phase 1 (fondation) — voir P1-FE-002

| ID         | Task                                                                                                                                                                                                                                                                  | PRD Ref        |
|------------|-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|----------------|
| P1-FE-001  | Scaffold Vue.js 3 SPA in `frontend/`. Run `pnpm create vue@latest` selecting: TypeScript NO, JSX NO, Vue Router YES, Pinia YES, ESLint YES, Prettier YES. Configure `vite.config.js` to proxy `/api` to Laravel backend (`target: 'http://localhost:8000'`) and output built assets to `backend/public/build/`. Add pnpm workspace root `pnpm-workspace.yaml` at repo root listing `frontend/`. | §8 Frontend    |
| P1-FE-002  | Install and configure Tailwind CSS 3: `pnpm add -D tailwindcss postcss autoprefixer`. Create `tailwind.config.js`: (1) `darkMode: 'class'`, (2) content paths `['./src/**/*.vue', '../../backend/resources/views/**/*.blade.php']`, (3) `theme.extend.fontFamily`: `sans: ['Inter', 'system-ui', '-apple-system', 'sans-serif']`, `mono: ['JetBrains Mono', 'ui-monospace', 'monospace']`, (4) `theme.extend.colors.brand`: full Emerald alias from `docs/graphics/colors.md` (50→900). Import directives in `src/assets/app.css`. Reference: `docs/graphics/colors.md`, `docs/graphics/polices.md`. | §8 CSS, Design System |
| P1-FE-003  | Create `resources/js/api/axios.js`. Export configured Axios instance: `baseURL = '/api/v1'`, `withCredentials: true`, `Content-Type: application/json`. Request interceptor: inject `Authorization: Bearer {token}` from localStorage key `znz_token`. Response interceptor: on 401 → clear token + redirect to `/login`. On 402 → redirect to `/subscription`.       | §11, §6.3      |
| P1-FE-004  | Create `resources/js/stores/auth.js` (Pinia store). State: `user: null`, `token: null`. Actions: `login(email, password)` calls `POST /auth/login`, stores token in localStorage `znz_token` + state. `register(payload)` calls `POST /auth/register`, same storage. `logout()` calls `POST /auth/logout`, clears state + localStorage. Getters: `isAuthenticated: bool`, `isOnActivePlan: bool` (checks subscription_status + trial_ends_at).  | US-07, §10     |
| P1-FE-005  | Create `resources/js/router/index.js`. Routes: `/ → Dashboard` (lazy, auth guard), `/login → LoginPage`, `/register → RegisterPage`. Navigation guard: if route requires auth and `!authStore.isAuthenticated` → redirect `/login`. If authenticated and visits `/login` → redirect `/`.                                                                              | §8 Frontend    |
| P1-FE-006  | Create `resources/js/layouts/AppLayout.vue`. Slots: `<slot />` for main content. Includes: `<NavBar />` at top. Props: none. Handles: shows trial expiry warning banner if `daysUntilTrialEnd < 3`.                                                                  | §8 Frontend    |
| P1-FE-007  | Create `resources/js/components/NavBar.vue`. Displays: business name from auth store, "New Reservation" button linking to form, "Logout" button. Responsive: collapses to hamburger on mobile (Tailwind `sm:` breakpoint). ARIA: `role="navigation"`, `aria-label="Main navigation"`.                                                                               | §5 US-06       |
| P1-FE-008  | Create `resources/js/pages/LoginPage.vue`. Form fields: email (type email), password (type password). Submit calls `authStore.login()`. Shows inline field errors from API 422 response. Shows general error on 401. Redirects to `/` on success. Link to `/register`. Accessible: labels linked to inputs via `for`/`id`, `aria-describedby` for errors.           | US-07          |
| P1-FE-009  | Create `resources/js/pages/RegisterPage.vue`. Fields: name, email, business_name, phone (type tel, placeholder +33612345678), password, password_confirmation. Submit calls `authStore.register()`. Maps 422 field errors to inline messages. Redirects to `/` on success. Link to `/login`.                                                                          | US-07          |
| P1-FE-010  | Create `resources/js/composables/useReservations.js`. Exports: `createReservation(payload): Promise`, `fetchReservations(params): Promise`, `fetchReservation(id): Promise`, `lookupCustomer(phone): Promise`. All use the configured Axios instance. Loading and error state per action. Returns `{data, loading, error, execute}` pattern.                        | US-01, §11     |
| P1-FE-011  | Create `resources/js/components/ReservationForm.vue`. Props: none (standalone form). Fields: customer_name (text), phone (tel), scheduled_at (datetime-local), guests (number, min 1), notes (textarea), phone_verified (checkbox labelled "Number confirmed by phone call"). On phone blur: call `lookupCustomer(phone)` → if found show `ReliabilityBadge` component. Submit calls `createReservation()`. Shows per-field validation errors from API. Emits `created(reservation)` on success, resets form. Must be completable in < 60 seconds.  | US-01, US-04   |
| P1-FE-012  | Create `resources/js/components/ReliabilityBadge.vue`. Props: `score: number\|null`, `tier: 'reliable'\|'average'\|'at_risk'\|null`. Renders: green pill "Reliable XX%" / orange pill "Average XX%" / red pill "At Risk XX%" / grey pill "No history". `aria-label`: "Reliability score: {label}". Used in ReservationForm and ReservationRow.                   | US-04, Feature 2 |
| P1-FE-013  | Create stub `resources/js/pages/Dashboard.vue`. Renders `<AppLayout>` wrapping `<ReservationForm>`. On `created` event from form: show success toast (inline, no library). Full dashboard implementation in Phase 3. Route `/`.                                       | US-06          |
| P1-FE-014  | Create `src/assets/app.css` CSS utility layer. Add `@layer components` block with classes from `docs/graphics/polices.md`: `.text-heading-1` through `.text-heading-4`, `.text-body`, `.text-body-sm`, `.text-label`, `.text-caption`, `.text-overline`, `.text-badge`. Add Google Fonts `<link>` preconnect tags to the SPA `index.html`: Inter (wght 400;500;600;700;800) + JetBrains Mono (wght 400;500). These classes must be usable in all components from Phase 1 onward. | Design System, docs/graphics/polices.md |

---

#### 4.1.6 Front-end Tests

| Test File                                             | Test Cases                                                                                                                                                                       |
|-------------------------------------------------------|----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `tests/js/unit/stores/auth.spec.js`                  | login stores token in localStorage; logout clears localStorage and state; isAuthenticated returns false with null token; isOnActivePlan false for expired trial                  |
| `tests/js/unit/composables/useReservations.spec.js`  | createReservation calls POST /reservations with payload; fetchReservations calls GET with date param; lookupCustomer returns score and tier; error state set on API failure      |
| `tests/js/unit/components/ReliabilityBadge.spec.js`  | renders green pill for score ≥ 90; orange for 70–89; red for < 70; grey for null; aria-label correct for each tier                                                              |
| `tests/js/component/ReservationForm.spec.js`          | submits form data to createReservation; shows inline errors for each field on 422; calls lookupCustomer on phone blur; shows ReliabilityBadge when customer found; phone_verified checkbox toggles payload field; resets after successful submission |
| `tests/js/component/LoginPage.spec.js`               | submits email and password on form submit; shows 401 error message; shows per-field errors on 422; redirects to / on success                                                     |
| `tests/js/unit/design-system.spec.js`                | .text-heading-1 class applies correct font-size and font-weight; .text-label applies text-sm font-medium; .text-overline applies uppercase tracking-widest; Inter font-family defined in Tailwind config; brand.500 color resolves to #10B981; darkMode config is 'class' |

---

#### 4.1.7 DevOps / Infrastructure Tasks

| ID         | Task                                                                                                                                                                                                                                                                        | PRD Ref        |
|------------|-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|----------------|
| P1-DO-001  | Set up monorepo structure at root: `backend/` (Laravel 12) and `frontend/` (Vue.js 3). Create `docker-compose.yml` at root for local dev. Services: `api` (PHP-FPM, built from `backend/docker/php/Dockerfile`), `nginx` (nginx:1.27-alpine, port 80, proxies to api + frontend), `worker` (`php artisan queue:work --tries=3 --sleep=1 --max-time=3600`), `scheduler` (`php artisan schedule:work`), `db` (postgres:16-alpine, port 5432, named volume), `redis` (redis:7-alpine, port 6379, named volume), `mailpit` (port 1025/8025). All share `znz_net` bridge network. DB and Redis have health checks. | §8 Infra       |
| P1-DO-002  | Create `backend/docker/php/Dockerfile`. Base: `php:8.3-fpm`. Install: apt deps (libpq-dev, libzip-dev, libicu-dev, etc.), PHP extensions (pdo_pgsql, mbstring, exif, pcntl, bcmath, gd, zip, intl, pdo_sqlite), PCOV for coverage (`pecl install pcov`), Redis extension (`pecl install redis`). Copy Composer from `composer:latest`. WORKDIR `/var/www/html`. PUID/PGID ARGs for www-data user mapping. Create `backend/docker/php/conf.d/memory.ini` (memory_limit = 512M). | §8 Infra       |
| P1-DO-003  | Create `backend/.env.example` with all required vars: APP_*, DB_* (host=db, port=5432), REDIS_HOST=redis, QUEUE_CONNECTION=redis, CACHE_STORE=redis, TWILIO_ACCOUNT_SID, TWILIO_AUTH_TOKEN, TWILIO_FROM, STRIPE_KEY, STRIPE_SECRET, STRIPE_WEBHOOK_SECRET, SENTRY_LARAVEL_DSN (placeholder), MAIL_* (Mailpit defaults for dev). | §10 Config     |
| P1-DO-004  | Create `.github/workflows/ci.yml`. Two jobs mirroring Koomky CI: `backend` job: postgres:16-alpine + redis:7-alpine services, PHP 8.3 (shivammathur/setup-php with pdo_pgsql/mbstring/pcov extensions), `composer install --working-dir=backend`, copy `.env.example → .env`, `php artisan key:generate`, wait for postgres, run `./vendor/bin/pint --test` (lint), `./vendor/bin/phpstan analyse -c phpstan.neon` (static analysis), run Unit tests file-by-file via `pest`, run Feature tests file-by-file via `pest --stop-on-failure`. `frontend` job: pnpm@10, Node 20, `pnpm install`, `pnpm lint`, `pnpm format:check`, `pnpm vitest run --coverage`. | §13 Phase 4    |

---

#### 4.1.8 Deliverables Checklist

- [ ] `docker compose up` starts all 6 services without errors on a fresh clone
- [ ] `php artisan migrate:fresh` runs all 4 migrations without error
- [ ] `POST /api/v1/auth/register` returns 201 with Sanctum token
- [ ] `POST /api/v1/auth/login` returns 200 with token; `POST /api/v1/auth/logout` returns 204
- [ ] `POST /api/v1/reservations` (unverified) dispatches `SendVerificationSms` job to queue
- [ ] `POST /api/v1/reservations` (phone_verified=true) does NOT dispatch SMS job; status = `pending_reminder`
- [ ] Queue worker processes `SendVerificationSms` and a real SMS arrives on a French mobile number (Twilio test verified)
- [ ] `GET /c/{token}` renders confirmation page with business + appointment details
- [ ] `POST /c/{token}/confirm` with action=confirm → reservation.status = `confirmed`
- [ ] `POST /c/{token}/confirm` with action=cancel → reservation.status = `cancelled_by_client`
- [ ] Token is invalidated after one use (second call returns 410)
- [ ] Expired token returns 410
- [ ] `php artisan test` passes with 0 failures
- [ ] GitHub Actions CI pipeline green on main
- [ ] Vue SPA loads at `/`, redirects to `/login` when unauthenticated
- [ ] Login and Register pages submit correctly and handle errors

---

## Section 5 — API Endpoints Delivered in Phase 1

| Method | Endpoint                       | Controller                        | Auth      | Notes                                                                                          |
|--------|--------------------------------|-----------------------------------|-----------|------------------------------------------------------------------------------------------------|
| POST   | /api/v1/auth/register          | AuthController::register          | No        | Body: {name, email, password, password_confirmation, business_name, phone}. Returns {token, business} |
| POST   | /api/v1/auth/login             | AuthController::login             | No        | Body: {email, password}. Returns {token, business}                                             |
| POST   | /api/v1/auth/logout            | AuthController::logout            | Bearer    | Deletes current token. Returns 204                                                             |
| POST   | /api/v1/reservations           | ReservationController::store      | Bearer+subscription | Body: {customer_name, phone, scheduled_at, guests?, notes?, phone_verified?}. Returns 201 {reservation, customer} |
| GET    | /api/v1/reservations           | ReservationController::index      | Bearer    | Query: ?date=YYYY-MM-DD or ?week=YYYY-WNN. Returns {reservations[], stats{}}                   |
| GET    | /api/v1/reservations/{id}      | ReservationController::show       | Bearer    | Returns {reservation, customer, sms_logs[]}                                                    |
| GET    | /api/v1/customers/lookup       | CustomerController::lookup        | Bearer    | Query: ?phone=+33XXX. Returns {found, reliability_score, score_tier}                           |
| GET    | /c/{token}                     | ConfirmationController::show      | No        | Returns HTML confirmation page                                                                 |
| POST   | /c/{token}/confirm             | ConfirmationController::confirm   | No        | Body: {action: confirm\|cancel}. Returns HTML result page                                      |
| POST   | /api/v1/webhooks/twilio        | TwilioWebhookController::handle   | No (stub) | Stub — logs payload, returns 200. Full implementation Phase 2                                  |

---

## Section 6 — Exit Criteria

| # | Criterion                                                                               | Validated |
|---|-----------------------------------------------------------------------------------------|-----------|
| 1 | All 4 DB migrations run on a fresh PostgreSQL 16 database without errors                | [ ]       |
| 2 | All 11 back-end test files pass (0 failures, 0 errors)                                  | [ ]       |
| 3 | All 6 front-end test files pass                                                         | [ ]       |
| 4 | Back-end code coverage ≥ 80% on Feature tests for Auth, Reservation, Confirmation       | [ ]       |
| 5 | GitHub Actions CI pipeline green on `main` branch                                       | [ ]       |
| 6 | A real SMS is delivered to a French mobile number via Twilio in staging environment     | [ ]       |
| 7 | Confirmation link confirms/cancels correctly and token is invalidated                   | [ ]       |
| 8 | Laravel Pint passes on all PHP files (`./vendor/bin/pint --test`)                       | [ ]       |
| 9 | ESLint passes on all Vue/JS files (`pnpm lint`)                                         | [ ]       |
| 10 | `POST /api/v1/reservations` returns 402 when trial has expired                          | [ ]       |
| 11 | Vue SPA builds without errors (`pnpm build`)                                            | [ ]       |
| 12 | `.env.example` documents all required environment variables with descriptions           | [ ]       |
| 13 | Google Fonts (Inter + JetBrains Mono) load in SPA; CSS utility classes available       | [ ]       |

---

## Section 7 — Risks Specific to Phase 1

| Risk                                                         | Probability | Impact | Mitigation                                                                                   |
|--------------------------------------------------------------|-------------|--------|----------------------------------------------------------------------------------------------|
| Twilio alphanumeric sender ID rejected for French numbers    | Medium      | High   | Test with long code (+33 number) as fallback; register alphanumeric sender with Twilio early |
| SMS not delivered to French operators (SFR/Orange/Bouygues) | Low         | High   | Test with SIM cards from all 3 operators during Sprint 1; use Twilio delivery logs           |
| PostgreSQL UUID extension not available on host              | Low         | Medium | Migration creates extension `pgcrypto` explicitly; tested in CI with fresh PG container      |
| Twilio SDK version incompatible with PHP 8.3                 | Low         | High   | Pin `twilio/sdk` version in `composer.json`; check compatibility matrix before install       |
| Queue worker silently drops failed jobs                      | Medium      | High   | Configure `QUEUE_FAILED_DRIVER=database`, create `failed_jobs` table, monitor in Phase 2 with Horizon |
| Confirmation token collision (UUID)                          | Very Low    | Low    | UNIQUE constraint on `confirmation_token` column; retry on constraint violation (virtually impossible) |

---

## Section 8 — External Dependencies

| Service/Library          | Phase 1 Usage                                      | Fallback if Unavailable                                   |
|--------------------------|----------------------------------------------------|-----------------------------------------------------------|
| Twilio                   | Send verification SMS, receive delivery webhooks   | Mock `SmsServiceInterface` in tests; manual SMS for demo  |
| PostgreSQL 16            | Primary datastore                                  | SQLite for unit/feature tests (already configured by Laravel) |
| Redis 7                  | Queue backend                                      | `QUEUE_CONNECTION=sync` for local testing only            |
| `twilio/sdk` PHP library | Twilio API client                                  | No fallback — required for production                     |
| GitHub Actions           | CI pipeline                                        | Run `php artisan test` locally before merge               |
| Docker                   | Local development environment                      | Manual PHP + PG + Redis setup with Herd/Valet             |
