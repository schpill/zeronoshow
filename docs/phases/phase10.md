# Phase 10 — Booking Widget

| Field            | Value                                                                 |
|------------------|-----------------------------------------------------------------------|
| **Phase**        | 10 of 10                                                              |
| **Name**         | Booking Widget — Public Self-Booking & Iframe Embed                   |
| **Duration**     | Weeks 27–30 (4 weeks)                                                 |
| **Milestone**    | M10 — Client self-service booking live                                |
| **PRD Sections** | §8 (Booking Widget), §9 (Public Access)                               |
| **Prerequisite** | Phase 9 fully completed and validated                                 |
| **Status**       | Not started                                                           |

---

## Section 1 — Phase Objectives

| ID        | Objective                                                                                                    |
|-----------|--------------------------------------------------------------------------------------------------------------|
| P10-OBJ-1 | Guests can complete a reservation autonomously on a public booking page without requiring a ZeroNoShow account |
| P10-OBJ-2 | Businesses can embed the booking widget as an iframe on their own website with a single script tag            |
| P10-OBJ-3 | Booking widget enforces real-time slot availability (no double-booking)                                       |
| P10-OBJ-4 | Guest receives immediate SMS confirmation and standard ZeroNoShow reminder pipeline                           |
| P10-OBJ-5 | Business dashboard shows widget-origin reservations distinguishable from manual entries                       |
| P10-OBJ-6 | Business can customize widget appearance (logo, accent colour) and configure booking rules                    |
| P10-OBJ-7 | Widget link/embed code is accessible from the business dashboard with one click                               |

---

## Section 2 — Entry Criteria

- Phase 9 (CRM + Reputation) merged and CI green on `main`
- `customers` table includes CRM fields and `phone` column uniqueness per business
- `reservations` table stable (no planned schema changes)
- Stripe subscription billing confirmed working (existing Léo add-on pattern)
- Twilio SMS pipeline reliable (Phase 1–4 baseline)
- Front-end build pipeline working (pnpm, Vite, Tailwind 3)
- Docker Compose local dev environment functional

---

## Section 3 — Scope — Requirement Traceability

| Requirement Group                        | ID Range       | Status   | Notes                                                            |
|------------------------------------------|----------------|----------|------------------------------------------------------------------|
| Public booking page (no auth)            | §8.1–§8.3      | Included |                                                                  |
| Iframe embed with script tag             | §8.4           | Included |                                                                  |
| Real-time slot availability              | §8.5           | Included | Derived from business operating hours + existing reservations    |
| Guest phone verification (OTP)           | §8.6           | Included | SMS OTP before reservation is confirmed                          |
| Widget appearance customization          | §8.7           | Included | Logo URL, accent colour (hex), business name display             |
| Booking rules configuration              | §8.8           | Included | Max party size, advance booking window, same-day cutoff          |
| Source tagging (widget vs manual)        | §8.9           | Included | `source` ENUM on `reservations` table                            |
| Custom booking confirmation page         | §8.10          | Partial  | Basic success page only; advanced branding deferred post-MVP     |
| Multi-language widget                    | §8.11          | No       | French only for MVP; i18n deferred                               |
| Calendar picker beyond 60 days           | §8.12          | No       | Widget limits advance booking to business-configured max         |

---

## Section 4 — Detailed Sprint Breakdown

### 4.15 Sprint 15 — Public Booking API & Widget Core (Weeks 27–28)

#### 4.15.1 Sprint Objectives

- Public unauthenticated API endpoints serve slot availability and accept reservations
- SMS OTP verification flow completes successfully for a guest phone number
- `reservations.source` field persists origin of booking
- Widget settings (logo, accent, rules) are configurable from dashboard and persisted
- Back-end test coverage ≥ 90% for new code

#### 4.15.2 Database Migrations

| Migration name                                | Description                                                                                                                                                                                                                                                                                                                                          |
|-----------------------------------------------|------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `add_source_to_reservations_table`            | Add `source` ENUM('manual','widget') NOT NULL DEFAULT 'manual' to `reservations`. Index: source (btree).                                                                                                                                                                                                                                            |
| `create_booking_otps_table`                   | id UUID PK, phone VARCHAR(20) NOT NULL, code VARCHAR(6) NOT NULL, expires_at TIMESTAMPTZ NOT NULL, used_at TIMESTAMPTZ nullable, ip_address VARCHAR(45) nullable, attempts SMALLINT NOT NULL DEFAULT 0, timestamps. Indexes: phone+expires_at composite (btree), expires_at (btree) for cleanup.                                                      |
| `create_widget_settings_table`                | id UUID PK, business_id UUID FK→businesses(id) ON DELETE CASCADE UNIQUE, logo_url VARCHAR(500) nullable, accent_colour CHAR(7) NOT NULL DEFAULT '#6366f1', max_party_size SMALLINT NOT NULL DEFAULT 20, advance_booking_days SMALLINT NOT NULL DEFAULT 60, same_day_cutoff_minutes SMALLINT NOT NULL DEFAULT 60, is_enabled BOOLEAN NOT NULL DEFAULT true, timestamps. Index: business_id (btree, unique). |

#### 4.15.3 Back-end Tasks

| ID          | Task                                                                                                                                                                                                                                                                   | PRD Ref |
|-------------|------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|---------|
| P10-BE-001  | Add migration `add_source_to_reservations_table` — add `source` ENUM('manual','widget') DEFAULT 'manual'; update `Reservation` model $casts; update `ReservationResource` to expose `source` field                                                                    | §8.9    |
| P10-BE-002  | Add migration `create_booking_otps_table` — columns: id UUID PK, phone VARCHAR(20), code VARCHAR(6), expires_at TIMESTAMPTZ, used_at TIMESTAMPTZ nullable, ip_address VARCHAR(45) nullable, attempts SMALLINT DEFAULT 0, timestamps                                    | §8.6    |
| P10-BE-003  | Add migration `create_widget_settings_table` — columns: id UUID PK, business_id UUID FK UNIQUE, logo_url nullable, accent_colour CHAR(7), max_party_size SMALLINT, advance_booking_days SMALLINT, same_day_cutoff_minutes SMALLINT, is_enabled BOOLEAN, timestamps     | §8.7    |
| P10-BE-004  | Create `BookingOtp` model (HasUuids) — fillable: phone, code, expires_at, used_at, ip_address, attempts; scopes: `valid(phone)` (unused + not expired + attempts < 5), `expired()` (for cleanup)                                                                      | §8.6    |
| P10-BE-005  | Create `WidgetSetting` model (HasUuids) — belongs to `Business`; fillable all columns; `getPublicConfigAttribute()` returns array safe for unauthenticated frontend (no business internal fields)                                                                      | §8.7    |
| P10-BE-006  | Create `BookingOtpService` — `send(phone, ip): void` (generate 6-digit code, store in `booking_otps`, dispatch `SendBookingOtpSms` job, throw `TooManyOtpRequestsException` if 3 sends within 10 min for same phone), `verify(phone, code): bool` (validate + mark used, increment attempts on failure, throw `OtpExpiredException` / `OtpInvalidException` / `OtpMaxAttemptsException`) | §8.6 |
| P10-BE-007  | Create `SendBookingOtpSms` job — queues SMS via `SmsServiceInterface` with message "Votre code de réservation ZeroNoShow : {code}. Valide 10 minutes."; retries: 2, timeout: 30s                                                                                      | §8.6    |
| P10-BE-008  | Create `SlotAvailabilityService` — `getAvailableSlots(business, date): array` returns time slots (HH:MM strings) where `reservations` count for that slot < capacity; respects `advance_booking_days` and `same_day_cutoff_minutes` from `WidgetSetting`; uses Redis cache key `slots:{business_id}:{date}` TTL 30s | §8.5 |
| P10-BE-009  | Create `PublicBookingController` — unauthenticated, route-model-bind business via `public_token` UUID on businesses table (add via migration `add_public_token_to_businesses_table`); methods: `config(business)` (return `WidgetSetting::getPublicConfigAttribute`), `slots(request, business)` (return available slots for date), `sendOtp(request, business)` (delegate to `BookingOtpService::send`), `verifyOtp(request, business)` (return signed `guest_token` JWT), `store(request, business)` (verify `guest_token` + create reservation with source='widget') | §8.1–§8.6 |
| P10-BE-010  | Add migration `add_public_token_to_businesses_table` — add `public_token` UUID UNIQUE NOT NULL (DB-generated with `gen_random_uuid()`). Index: public_token (btree unique). Update `Business` model and `BusinessResource` to expose `public_token`                    | §8.4    |
| P10-BE-011  | Create `SendOtpRequest` — validate: phone (required, regex E.164 `/^\+[1-9]\d{7,14}$/`); French error messages. Throttle: 3 per 10 min per phone (Laravel rate limiter `widget-otp:{phone}`)                                                                          | §8.6    |
| P10-BE-012  | Create `VerifyOtpRequest` — validate: phone (required, regex E.164), code (required, digits, size:6)                                                                                                                                                                   | §8.6    |
| P10-BE-013  | Create `PublicStoreReservationRequest` — validate: guest_token (required, string), party_size (required, integer 1–max_party_size), date (required, date, after_or_equal:today, within advance_booking_days), time (required, date_format:H:i), guest_name (required, string, max:100), guest_phone (required, regex E.164). Verify guest_token signature matches guest_phone                         | §8.1    |
| P10-BE-014  | Create `GuestToken` service — `issue(phone): string` returns HMAC-SHA256 signed JWT (payload: phone, business_id, issued_at, exp: +30min, secret: `APP_KEY`); `verify(token): array` throws `InvalidGuestTokenException` on tamper/expiry                             | §8.6    |
| P10-BE-015  | Create `WidgetSettingController` (authenticated) — methods: `show(business)` (return `WidgetSettingResource`), `update(UpdateWidgetSettingRequest, business)` (upsert setting); authorize via `BusinessPolicy::manage`                                                 | §8.7    |
| P10-BE-016  | Create `UpdateWidgetSettingRequest` — validate: logo_url (nullable, url, max:500), accent_colour (nullable, regex `/^#[0-9A-Fa-f]{6}$/`), max_party_size (nullable, integer 1–100), advance_booking_days (nullable, integer 1–365), same_day_cutoff_minutes (nullable, integer 0–1440), is_enabled (nullable, boolean) | §8.7 |
| P10-BE-017  | Create `WidgetSettingResource` — expose all `WidgetSetting` fields + `embed_url` (computed: `config('app.url').'/widget/'.$business->public_token`) + `booking_url` (same URL for direct link)                                                                         | §8.7    |
| P10-BE-018  | Create `PurgeExpiredBookingOtps` Artisan command — delete `booking_otps` where `expires_at < now()`; register in `Kernel.php` schedule daily at 03:00                                                                                                                  | §8.6    |
| P10-BE-019  | Register all public widget routes in `routes/api.php` under prefix `/public/widget/{businessToken}` with `throttle:widget` middleware (60 req/min); register authenticated widget settings routes under `api/v1/businesses/{business}/widget` with `auth:sanctum`     | §8.4    |

#### 4.15.4 Back-end Tests (TDD)

| Test File                                                 | Test Cases                                                                                                                                                                                                                                                             |
|-----------------------------------------------------------|------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `tests/Feature/Widget/PublicBookingConfigTest.php`        | returns 200 with public config for valid token, returns 404 for unknown token, disabled widget returns 423 Locked, logo_url and accent_colour included in response                                                                                                     |
| `tests/Feature/Widget/SlotAvailabilityTest.php`           | returns available slots for valid date, excludes slots at capacity, respects same_day_cutoff_minutes (past cutoff slots absent), excludes dates beyond advance_booking_days, returns empty array for fully booked day, uses Redis cache (second call hits cache)        |
| `tests/Feature/Widget/OtpFlowTest.php`                   | sendOtp sends SMS and stores OTP, sendOtp throttled after 3 sends in 10 min (429), verifyOtp returns guest_token on valid code, verifyOtp fails on wrong code and increments attempts, verifyOtp fails after 5 wrong attempts (locked), verifyOtp fails on expired OTP |
| `tests/Feature/Widget/PublicReservationStoreTest.php`     | creates reservation with source=widget on valid guest_token, returns 422 on party_size exceeds max, returns 422 on date beyond advance_booking_days, returns 422 on invalid guest_token, returns 422 on expired guest_token, returns 409 if slot no longer available, sends SMS confirmation after creation, reservation visible in dashboard |
| `tests/Feature/Widget/WidgetSettingControllerTest.php`    | unauthenticated cannot access settings, authenticated business owner can show settings, authenticated can update logo_url and accent_colour, invalid accent_colour rejected, update returns 422 on logo_url exceeding 500 chars, embed_url and booking_url computed correctly |
| `tests/Unit/BookingOtpServiceTest.php`                    | send generates 6-digit code, send dispatches SendBookingOtpSms job, send throws TooManyOtpRequestsException after 3 sends in 10 min, verify returns true for valid code, verify returns false and increments attempts on wrong code, verify throws OtpExpiredException, verify throws OtpMaxAttemptsException at attempt 5 |
| `tests/Unit/GuestTokenTest.php`                           | issue returns signed JWT, verify decodes valid token, verify throws on tampered payload, verify throws on expired token                                                                                                                                                 |
| `tests/Unit/SlotAvailabilityServiceTest.php`              | slot is available when count < capacity, slot is unavailable when count >= capacity, cutoff removes past-cutoff time today, advance window excludes too-far dates                                                                                                       |

#### 4.15.5 Front-end Tasks

| ID          | Task                                                                                                                                                                                                                                 | PRD Ref |
|-------------|--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|---------|
| P10-FE-001  | Create `src/api/widget.ts` — typed API client for public widget endpoints: `getWidgetConfig(token)`, `getSlots(token, date)`, `sendOtp(token, phone)`, `verifyOtp(token, phone, code)`, `createReservation(token, payload)`; no auth header; base URL configurable via `VITE_API_URL` | §8.1    |
| P10-FE-002  | Create `src/views/public/BookingWidgetView.vue` — full-page public booking view mounted at `/widget/:businessToken`; fetches widget config on mount; renders `BookingStepDate`, `BookingStepGuest`, `BookingStepOtp`, `BookingStepConfirm` in order; handles disabled widget (shows closed message); no navbar/footer | §8.1 |
| P10-FE-003  | Create `src/views/public/BookingSuccessView.vue` — displayed after successful reservation; shows business name, date, time, party_size, guest_name; renders "Vous recevrez un SMS de confirmation" message; no auth required            | §8.1    |
| P10-FE-004  | Create `src/components/booking/BookingStepDate.vue` — date + time slot picker; fetches slots for selected date via `widget.ts`; renders calendar month view (pure CSS, no external date-picker lib); renders time slot grid; emits `select(date, time)` when both chosen; shows "Aucun créneau disponible" on empty slots | §8.5 |
| P10-FE-005  | Create `src/components/booking/BookingStepGuest.vue` — guest details form: guest_name (text, required), guest_phone (tel, E.164 format hint), party_size (number 1–max from config); validates on submit; emits `submit(guestDetails)`; all labels in French                                                          | §8.1    |
| P10-FE-006  | Create `src/components/booking/BookingStepOtp.vue` — renders 6-digit OTP input (6 individual character inputs with auto-focus-next); sends OTP on mount; shows "Renvoyer le code" after 60s countdown; calls `verifyOtp` on complete 6-digit entry; emits `verified(guestToken)` on success; shows error on wrong code | §8.6 |
| P10-FE-007  | Create `src/components/booking/BookingStepConfirm.vue` — summary card (date, time, party_size, guest_name, phone); "Confirmer ma réservation" button; calls `createReservation`; on success navigates to `BookingSuccessView`; on 409 shows "Ce créneau vient d'être pris, choisissez un autre horaire" and resets to step 1 | §8.1 |
| P10-FE-008  | Create `src/composables/useBookingWidget.ts` — manages multi-step flow state: currentStep (date/guest/otp/confirm), selectedDate, selectedTime, guestDetails, guestToken, widgetConfig; exposes `goTo(step)`, `reset()`; drives `BookingWidgetView` rendering            | §8.1    |
| P10-FE-009  | Add public route `/widget/:businessToken` to `router/index.ts` — no `requiresAuth` meta; no `guestOnly` meta; layout: none (standalone page)                                                                                         | §8.4    |
| P10-FE-010  | Create `src/components/widget/WidgetSettingsCard.vue` — dashboard card (authenticated); shows current logo, accent colour preview, max party size, advance booking days, cutoff, enabled toggle; "Modifier" button opens `WidgetSettingsModal`                          | §8.7    |
| P10-FE-011  | Create `src/components/widget/WidgetSettingsModal.vue` — form with: logo_url (text), accent_colour (color input + hex text), max_party_size, advance_booking_days, same_day_cutoff_minutes, is_enabled toggle; submits PUT to `WidgetSettingController`; validation error display | §8.7  |
| P10-FE-012  | Create `src/components/widget/WidgetEmbedCard.vue` — shows booking URL (copy-to-clipboard button), iframe embed code snippet (copy-to-clipboard), preview thumbnail (iframe embedded at 50% scale); "Ouvrir le widget" link in new tab                                  | §8.4    |
| P10-FE-013  | Create `src/api/widgetSettings.ts` — authenticated API client: `getWidgetSettings(businessId)`, `updateWidgetSettings(businessId, payload)`; uses standard auth axios instance                                                       | §8.7    |
| P10-FE-014  | Create `src/composables/useWidgetSettings.ts` — reactive `settings`, `loading`, `error`; `fetch()`, `update(payload)` methods; used by `WidgetSettingsCard` and `WidgetSettingsModal`                                                | §8.7    |
| P10-FE-015  | Update `src/views/LeoView.vue` — add `WidgetSettingsCard` and `WidgetEmbedCard` as a new "Widget de réservation" section (visible for all businesses, no additional paywall — widget is a core Léo feature)                          | §8.7    |

#### 4.15.6 Front-end Tests

| Test File                                                      | Test Cases                                                                                                                                                                                           |
|----------------------------------------------------------------|------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `src/components/booking/__tests__/BookingStepDate.test.ts`     | renders calendar for current month, fetches slots on date click, shows time slots after date select, shows "Aucun créneau" when slots empty, emits select when date and time both chosen              |
| `src/components/booking/__tests__/BookingStepGuest.test.ts`    | renders all form fields, validates required fields on submit, validates party_size within max, emits submit with valid data                                                                           |
| `src/components/booking/__tests__/BookingStepOtp.test.ts`      | renders 6 individual inputs, auto-focuses next input on digit entry, calls verifyOtp on 6th digit, shows error on wrong code, shows resend button after 60s, calls sendOtp on resend                 |
| `src/components/booking/__tests__/BookingStepConfirm.test.ts`  | renders reservation summary correctly, calls createReservation on confirm click, navigates to success on 201, shows slot-taken error and resets on 409                                               |
| `src/composables/__tests__/useBookingWidget.test.ts`           | starts at date step, goTo changes step, reset clears all state, guestToken stored after OTP verified                                                                                                 |
| `src/components/widget/__tests__/WidgetSettingsCard.test.ts`   | renders current settings, opens modal on click, enabled toggle visible                                                                                                                               |
| `src/components/widget/__tests__/WidgetEmbedCard.test.ts`      | shows booking URL, copy button copies URL to clipboard, shows iframe code snippet, preview iframe renders                                                                                            |

#### 4.15.7 DevOps / Infrastructure Tasks

| ID          | Task                                                                                                                                 | PRD Ref |
|-------------|--------------------------------------------------------------------------------------------------------------------------------------|---------|
| P10-DO-001  | Add `VITE_WIDGET_BASE_URL` to `.env.example` and `frontend/.env.example` — defaults to `http://localhost` for local dev             | §8.4    |
| P10-DO-002  | Update `nginx.conf` to serve `/widget/*` route from the SPA `index.html` (already a SPA catch-all but verify no conflict with `/api/public/widget` prefix) | §8.4 |
| P10-DO-003  | Add `BOOKING_OTP_TTL_MINUTES` to `.env.example` (default 10) and wire in `BookingOtpService`                                        | §8.6    |

#### 4.15.8 Deliverables Checklist

- [ ] Migration `add_source_to_reservations_table` applied without errors
- [ ] Migration `create_booking_otps_table` applied without errors
- [ ] Migration `create_widget_settings_table` applied without errors
- [ ] Migration `add_public_token_to_businesses_table` applied without errors; existing businesses have auto-generated UUIDs
- [ ] `GET /api/public/widget/{token}/config` returns 200 for valid token, 404 for invalid
- [ ] `GET /api/public/widget/{token}/slots?date=YYYY-MM-DD` returns available time slots
- [ ] `POST /api/public/widget/{token}/otp/send` dispatches SMS job
- [ ] `POST /api/public/widget/{token}/otp/verify` returns guest_token JWT on valid code
- [ ] `POST /api/public/widget/{token}/reservations` creates reservation with source='widget'
- [ ] OTP throttling: 3 sends per 10 min per phone enforced (returns 429)
- [ ] Guest token tamper/expiry protection verified
- [ ] `PATCH /api/v1/businesses/{id}/widget` updates settings (authenticated)
- [ ] `BookingStepDate` renders slots fetched from API
- [ ] `BookingStepOtp` 6-input auto-focus flow works
- [ ] All Phase 10 Sprint 15 Pest tests pass
- [ ] All Phase 10 Sprint 15 Vitest tests pass
- [ ] CI pipeline green

---

### 4.16 Sprint 16 — Widget Polish, Embed & Dashboard Integration (Weeks 29–30)

#### 4.16.1 Sprint Objectives

- Widget is fully embeddable via `<iframe>` on any external website
- Widget respects business accent colour for primary buttons and highlights
- Dashboard shows "Réservations depuis le widget" count and source filter
- Reservation list supports filtering by source (manual / widget)
- `PurgeExpiredBookingOtps` command runs reliably in scheduler
- Front-end test coverage ≥ 85%; back-end coverage ≥ 90%
- Full E2E booking flow (slots → OTP → confirm → success page) verified with Playwright

#### 4.16.2 Database Migrations

*(No new migrations in Sprint 16)*

#### 4.16.3 Back-end Tasks

| ID          | Task                                                                                                                                                                                                                       | PRD Ref |
|-------------|----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|---------|
| P10-BE-020  | Update `ReservationResource` — add `source` field (already added in P10-BE-001); ensure `ReservationCollection` includes source in each item response                                                                    | §8.9    |
| P10-BE-021  | Update `ReservationController::index()` — support `?filter[source]=widget` query param (add to existing Spatie `allowedFilters` list); return filtered `ReservationCollection`                                            | §8.9    |
| P10-BE-022  | Create `WidgetStatsResource` — `widget_reservations_count` (total), `widget_reservations_this_month`, `widget_conversion_rate` (otps_sent / reservations_created last 30 days); served from `WidgetSettingController::stats()` | §8.9 |
| P10-BE-023  | Update `WidgetSettingController` — add `stats(business): JsonResponse` method returning `WidgetStatsResource`; authorize via `BusinessPolicy::manage`                                                                      | §8.9    |
| P10-BE-024  | Add X-Frame-Options and Content-Security-Policy middleware exception for `/widget/*` routes — by default Laravel sends `X-Frame-Options: SAMEORIGIN`; create `AllowIframeForWidget` middleware that sets `X-Frame-Options: ALLOWALL` only on public widget routes | §8.4 |
| P10-BE-025  | Add `booking:purge-otps` Artisan command as alias for `PurgeExpiredBookingOtps`; verify daily schedule registration; add `--dry-run` flag that prints count without deleting                                              | §8.6    |
| P10-BE-026  | Create `BookingWidgetSeeder` — seeds a test business with `WidgetSetting`, generates `public_token`, creates sample `booking_otps` for dev testing; added to `DatabaseSeeder` behind `App::isLocal()` guard               | §8.7    |
| P10-BE-027  | Add `widget_reservations_count` to `BusinessResource` — computed from `reservations()->where('source','widget')->count()` with eager-load guard (no N+1); expose in dashboard business show response                      | §8.9    |

#### 4.16.4 Back-end Tests (TDD)

| Test File                                                        | Test Cases                                                                                                                                                              |
|------------------------------------------------------------------|-------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `tests/Feature/Widget/ReservationSourceFilterTest.php`           | filter[source]=widget returns only widget reservations, filter[source]=manual returns only manual, no filter returns all, invalid filter value returns 422              |
| `tests/Feature/Widget/WidgetStatsTest.php`                       | returns correct total count, returns this-month count, conversion rate computed correctly with zero denominator guard                                                   |
| `tests/Feature/Widget/IframeHeaderTest.php`                      | public widget routes return X-Frame-Options: ALLOWALL, authenticated dashboard routes retain SAMEORIGIN, other public routes retain SAMEORIGIN                          |
| `tests/Feature/Widget/PurgeOtpsCommandTest.php`                  | command deletes expired OTPs only, leaves unexpired OTPs intact, dry-run prints count without deleting                                                                  |

#### 4.16.5 Front-end Tasks

| ID          | Task                                                                                                                                                                                                               | PRD Ref |
|-------------|--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|---------|
| P10-FE-020  | Update `BookingWidgetView.vue` — apply `accent_colour` from widget config as CSS custom property `--widget-accent` on the root container; use `var(--widget-accent)` for button background, border-focus, link colour | §8.7  |
| P10-FE-021  | Create `src/views/public/WidgetIframeEntrypoint.vue` — minimal wrapper that mounts `BookingWidgetView` with `postMessage` communication to parent frame: emit `zns:resize` on content height change; emit `zns:booked` on success; listen for `zns:reset` from parent to restart flow | §8.4 |
| P10-FE-022  | Update `src/components/widget/WidgetEmbedCard.vue` — generate iframe embed code using `WidgetIframeEntrypoint` URL; add optional `postMessage` listener snippet for parent page; show "Copier l'iframe" and "Copier le lien direct" as separate buttons | §8.4 |
| P10-FE-023  | Update `src/views/ReservationListView.vue` — add "Source" column to the reservations table (badge: "Widget" or "Manuel"); add source filter dropdown (Toutes / Widget / Manuel) that sets `?filter[source]` query param | §8.9 |
| P10-FE-024  | Create `src/components/widget/WidgetStatsCard.vue` — shows widget_reservations_count, widget_reservations_this_month, conversion_rate (formatted as %, e.g. "12 %"); fetches from `GET /api/v1/businesses/{id}/widget/stats` | §8.9 |
| P10-FE-025  | Update `src/views/LeoView.vue` — add `WidgetStatsCard` next to `WidgetSettingsCard` in the widget section                                                                                                         | §8.9    |
| P10-FE-026  | Add Playwright E2E test `e2e/booking-widget.spec.ts` — full flow: navigate to `/widget/{token}`, select date + slot, fill guest form, enter OTP (mocked via Mailpit or test override), confirm, land on success page; verify reservation appears in dashboard with source=widget | §8.1 |
| P10-FE-027  | Update `src/composables/useReservations.ts` — add `sourceFilter` ref and include in query params; reset to page 1 on source filter change                                                                          | §8.9    |

#### 4.16.6 Front-end Tests

| Test File                                                        | Test Cases                                                                                                                                                  |
|------------------------------------------------------------------|-------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `src/components/widget/__tests__/WidgetStatsCard.test.ts`        | renders reservation counts, renders conversion rate as percentage, shows loading state, shows error state                                                   |
| `src/components/widget/__tests__/WidgetEmbedCard.test.ts`        | separate copy buttons for iframe and direct link, iframe code includes correct URL, postMessage snippet shown                                               |
| `src/views/__tests__/ReservationListView.test.ts`                | source column renders "Widget" or "Manuel" badge, source filter dropdown changes displayed rows, all filter shows all rows                                   |
| `e2e/booking-widget.spec.ts`                                     | full end-to-end: date selection → slot selection → guest form → OTP → confirm → success page → reservation appears in dashboard with source=widget          |

#### 4.16.7 DevOps / Infrastructure Tasks

| ID          | Task                                                                                                                                                         | PRD Ref |
|-------------|--------------------------------------------------------------------------------------------------------------------------------------------------------------|---------|
| P10-DO-020  | Add CI step to GitHub Actions `frontend` job: run `npx playwright test e2e/booking-widget.spec.ts` with `VITE_API_URL` pointing to the test API container   | §8.1    |
| P10-DO-021  | Update `.env.example` with `PUBLIC_WIDGET_OTP_MOCK_CODE` (dev/test only) — allows test suite to bypass SMS by using a hardcoded code when set; documented in Audit Notes | §8.6 |
| P10-DO-022  | Document in Audit Notes: how to disable the widget for a business (is_enabled=false), how to regenerate public_token (SQL UPDATE), iframe X-Frame-Options setup | §8.4 |

#### 4.16.8 Deliverables Checklist

- [ ] Widget applies business accent colour to all primary interactive elements
- [ ] `<iframe src="/widget/{token}">` embeds the booking flow on an external page
- [ ] `postMessage` events `zns:resize` and `zns:booked` fire correctly in iframe mode
- [ ] `?filter[source]=widget` returns only widget reservations
- [ ] Reservation list view shows Source column and filter dropdown
- [ ] `WidgetStatsCard` shows correct counts and conversion rate
- [ ] `booking:purge-otps --dry-run` prints count without deleting
- [ ] Playwright E2E booking widget test passes
- [ ] CI pipeline green on `main`
- [ ] Front-end coverage ≥ 85%
- [ ] Back-end coverage ≥ 90%

---

## Section 5 — API Endpoints Delivered in Phase 10

| Method | Endpoint                                              | Controller                     | Auth   | Notes                                                                                                          |
|--------|-------------------------------------------------------|--------------------------------|--------|----------------------------------------------------------------------------------------------------------------|
| GET    | `/api/public/widget/{businessToken}/config`           | `PublicBookingController`      | No     | Returns `WidgetSetting::getPublicConfigAttribute()` — logo_url, accent_colour, max_party_size, advance_booking_days, same_day_cutoff_minutes, is_enabled |
| GET    | `/api/public/widget/{businessToken}/slots`            | `PublicBookingController`      | No     | Query: `?date=YYYY-MM-DD`. Returns `{slots: ["09:00","09:30",…]}`. Throttle: 60/min                           |
| POST   | `/api/public/widget/{businessToken}/otp/send`         | `PublicBookingController`      | No     | Body: `{phone}`. Dispatches `SendBookingOtpSms`. 429 after 3 sends in 10 min                                  |
| POST   | `/api/public/widget/{businessToken}/otp/verify`       | `PublicBookingController`      | No     | Body: `{phone, code}`. Returns `{guest_token}` JWT on success                                                 |
| POST   | `/api/public/widget/{businessToken}/reservations`     | `PublicBookingController`      | No     | Body: `{guest_token, party_size, date, time, guest_name, guest_phone}`. Returns 201 + `ReservationResource`   |
| GET    | `/api/v1/businesses/{business}/widget`                | `WidgetSettingController`      | Bearer | Returns `WidgetSettingResource` with embed_url, booking_url                                                   |
| PATCH  | `/api/v1/businesses/{business}/widget`                | `WidgetSettingController`      | Bearer | Body: `{logo_url?, accent_colour?, max_party_size?, advance_booking_days?, same_day_cutoff_minutes?, is_enabled?}` |
| GET    | `/api/v1/businesses/{business}/widget/stats`          | `WidgetSettingController`      | Bearer | Returns `{widget_reservations_count, widget_reservations_this_month, widget_conversion_rate}`                  |
| GET    | `/api/v1/reservations`                                | `ReservationController`        | Bearer | Existing endpoint — now supports `?filter[source]=widget\|manual`                                              |

---

## Section 6 — Exit Criteria

| # | Criterion                                                                                      | Validated |
|---|------------------------------------------------------------------------------------------------|-----------|
| 1 | All functional requirements in §3 with status "Included" are implemented and tested            | [ ]       |
| 2 | Back-end test coverage ≥ 90% for Phase 10 code                                                 | [ ]       |
| 3 | Front-end test coverage ≥ 85% for Phase 10 code                                                | [ ]       |
| 4 | PHPStan level 8 passes with zero errors                                                        | [ ]       |
| 5 | Pint passes with zero formatting issues                                                        | [ ]       |
| 6 | ESLint + Prettier check passes                                                                 | [ ]       |
| 7 | All Pest feature and unit tests green                                                          | [ ]       |
| 8 | All Vitest unit and component tests green                                                      | [ ]       |
| 9 | Playwright E2E `booking-widget.spec.ts` passes in CI                                           | [ ]       |
| 10 | CI pipeline green on `main` (backend + frontend jobs)                                         | [ ]       |
| 11 | Widget iframe embeds on an external page without X-Frame-Options blocking                      | [ ]       |
| 12 | OTP brute-force protection (5 attempts → locked) verified by test                              | [ ]       |
| 13 | Slot double-booking race condition handled (409 response tested)                               | [ ]       |
| 14 | `public_token` auto-generated for all existing businesses on migration                         | [ ]       |
| 15 | Accent colour applied to widget buttons via CSS custom property                                | [ ]       |
| 16 | `WidgetStatsCard` displays correct counts verified against seeded data                         | [ ]       |
| 17 | `booking:purge-otps` command scheduled daily and `--dry-run` flag functional                  | [ ]       |
| 18 | Reservation source filter `?filter[source]=widget` returns correct subset                      | [ ]       |

---

## Section 7 — Risks Specific to Phase 10

| Risk                                            | Probability | Impact | Mitigation                                                                                                                  |
|-------------------------------------------------|-------------|--------|-----------------------------------------------------------------------------------------------------------------------------|
| OTP SMS delivery delay confuses users           | Medium      | High   | Resend button with 60s cooldown; error message explains SMS delay; test with Twilio sandbox in CI                          |
| Slot race condition (two guests book same slot) | Medium      | High   | Use DB-level `SELECT ... FOR UPDATE` in `SlotAvailabilityService`; return 409 on conflict; test with concurrent requests    |
| iframe CSP blocked by external website          | Medium      | Medium | Document in embed instructions that CSP must allow `frame-src app-domain`; provide copy-paste snippet with instructions    |
| `public_token` enumeration attack               | Low         | High   | UUID v4 tokens have 2^122 entropy; add rate limiting (60 req/min per IP) on all public widget routes                       |
| OTP spoofing / replay attack                    | Low         | High   | OTP marked `used_at` on first use; subsequent use rejected; attempts tracked; token expiry enforced                        |
| Widget perf on mobile (large slot grid)         | Medium      | Medium | Lazy-load slots per date (not all dates upfront); paginate or virtualise if >48 slots; test on 4G throttled network in Playwright |
| Business accent colour inaccessible (low contrast) | Medium  | Low    | Warn in dashboard if contrast ratio < 4.5:1 (WCAG AA) using `color-contrast` computation; default `#6366f1` passes WCAG AA |

---

## Section 8 — External Dependencies

| Service/Library         | Phase 10 Usage                                                              | Fallback if Unavailable                                              |
|-------------------------|-----------------------------------------------------------------------------|----------------------------------------------------------------------|
| Twilio SMS              | Sending OTP codes to guests                                                 | `PUBLIC_WIDGET_OTP_MOCK_CODE` env for test/dev; no production fallback |
| Stripe                  | No new integration — widget is included in Léo add-on subscription         | N/A                                                                  |
| Redis                   | Slot availability cache (30s TTL); OTP rate-limiter counters               | Falls back to DB queries (slower); rate limiter degrades gracefully  |
| Browser Clipboard API   | Copy-to-clipboard for embed code in dashboard                               | Fallback: select-all text in a `<textarea>` for manual copy         |
| Playwright (CI)         | E2E booking widget test                                                     | Skip E2E job if Playwright install fails; alert in CI summary        |

---

## Assumptions

> The following assumptions were made during spec generation. Review and adjust before implementation begins.

- Booking widget is included in the Léo add-on (9€/month) — no additional paywall. If the product decision changes, add a feature flag gated on a new subscription plan.
- Businesses have operating hours stored somewhere (used by `SlotAvailabilityService`). If not, a simple configurable `opening_time` / `closing_time` + `slot_duration_minutes` on `WidgetSetting` should be added as a prerequisite task.
- The existing `reservations` table has a `time` or `starts_at` column allowing slot-level queries. Verify column name and adapt `SlotAvailabilityService` accordingly.
- One Twilio phone number is shared for OTP sends (same as existing SMS pipeline). No separate number is needed.
- French is the only supported language for MVP. i18n infrastructure can be added post-launch.
- Businesses do not yet have a capacity-per-slot concept beyond "existing reservations count". If capacity per time slot needs to be configurable, add a `slot_capacity` column to `WidgetSetting`.
