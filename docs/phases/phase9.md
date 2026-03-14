# Phase 9 — Lightweight CRM & Automated Reputation

| Field            | Value                                                                                              |
|------------------|----------------------------------------------------------------------------------------------------|
| **Phase**        | 9 of 10                                                                                             |
| **Name**         | Lightweight CRM & Automated Reputation — Client profiles + post-visit review requests             |
| **Duration**     | Weeks 23–26 (4 weeks)                                                                               |
| **Milestone**    | M9 — 3 pilot businesses have VIP/blacklist tags in use and ≥ 10 review requests sent in staging   |
| **PRD Sections** | PRD §2 no-show prevention, §3 target users; Conversation 2026-03-14                               |
| **Prerequisite** | Phase 8 fully completed and validated (voice calls live, observer chain stable)                    |
| **Status**       | Not started                                                                                         |

---

## Section 1 — Phase Objectives

| ID        | Objective                                                                                                                            | Verifiable?                     |
|-----------|--------------------------------------------------------------------------------------------------------------------------------------|---------------------------------|
| P9-OBJ-1  | Business owner can add notes, set VIP flag, set blacklist flag, and record birthday month/day on a customer profile                  | Feature test passes             |
| P9-OBJ-2  | Blacklisted customers trigger a warning banner when a reservation is created for their phone number                                  | Feature test passes             |
| P9-OBJ-3  | Dashboard customer list supports filtering by VIP, blacklisted, and birthday-this-month                                             | Feature test passes             |
| P9-OBJ-4  | After a reservation is marked as `show`, a review request SMS is automatically sent after a configurable delay                       | Feature test passes             |
| P9-OBJ-5  | Review request contains a direct deep-link to Google Maps or TripAdvisor review page for the business                               | Unit test passes                |
| P9-OBJ-6  | Review request clicks are tracked (redirect via ZeroNoShow URL shortener) and visible in the dashboard                              | Feature test passes             |
| P9-OBJ-7  | Business owner can configure review platform (Google / TripAdvisor), delay (0–48h), and enabled/disabled state per establishment   | Feature test passes             |
| P9-OBJ-8  | Backend test coverage ≥ 80%, frontend ≥ 80%                                                                                         | CI coverage gate passes         |

---

## Section 2 — Entry Criteria

- Phase 8 exit criteria all validated
- Existing `customers` table and `Customer` model stable (Phase 1)
- Reservation `show` status workflow stable (Phase 2)
- `CLAUDE.md` updated to reference Phase 9 tasks

---

## Section 3 — Scope — Requirement Traceability

| Requirement Group                         | Source Ref       | Included?  | Notes                                                                                              |
|-------------------------------------------|------------------|------------|----------------------------------------------------------------------------------------------------|
| Customer CRM fields (notes, VIP, blacklist, birthday) | Conversation | Yes   | Fields added to `customers` table. No new table.                                                  |
| Blacklist warning on reservation creation | Conversation     | Yes        | Backend returns flag in customer lookup; frontend shows banner.                                    |
| Customer list with CRM filters            | Conversation     | Yes        | Existing dashboard customer list extended with filter chips.                                       |
| Post-visit review request (SMS)           | Conversation     | Yes        | Triggered X hours after `show` status via `SendReviewRequestJob`.                                 |
| Review click tracking                     | Conversation     | Yes        | Redirect via `/r/{shortCode}` increments `clicked_at` and forwards.                               |
| Google My Business API integration        | Conversation     | Partial    | Deep-link only (no API auth required). Review URL constructed from `place_id`. API auth deferred. |
| TripAdvisor integration                   | Conversation     | Partial    | Deep-link only (`tripadvisor.fr/ShowUserReviews-{id}`). Full API deferred.                        |
| Review incentives (discounts)             | Conversation     | No         | Deferred — legal risk around incentivised reviews (Google ToS).                                   |
| Preferred table notes                     | Conversation     | Yes        | Simple text field on customer profile.                                                             |
| Birthday automated SMS                    | Conversation     | No         | Deferred — requires marketing consent workflow (RGPD). Can be Phase 10 polish.                    |

---

## Section 4 — Detailed Sprint Breakdown

### 4.13 Sprint 13 — CRM Backend & Customer Enrichment (Weeks 23–24)

#### 4.13.1 Sprint Objectives

- CRM fields migrated on `customers` table (notes, VIP, blacklist, birthday, preferred_table_notes)
- `CustomerCrmController` update endpoint operational
- Blacklist check returns flag on reservation creation endpoint
- Review request infrastructure: `review_requests` table, `SendReviewRequestJob`, deep-link generation
- `ReservationObserver` dispatches `SendReviewRequestJob` when status → show

---

#### 4.13.2 Database Migrations

| Migration name                                  | Description                                                                                                                                                                                                                                                                                                                              |
|-------------------------------------------------|------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `add_crm_fields_to_customers_table`             | Add `notes TEXT nullable`, `is_vip BOOLEAN NOT NULL DEFAULT false`, `is_blacklisted BOOLEAN NOT NULL DEFAULT false`, `birthday_month TINYINT nullable` (1–12), `birthday_day TINYINT nullable` (1–31), `preferred_table_notes VARCHAR(255) nullable`. Indexes: `is_vip` btree, `is_blacklisted` btree, `(birthday_month, birthday_day)` composite btree. |
| `create_review_requests_table`                  | id UUID PK, reservation_id UUID FK→reservations(id) ON DELETE CASCADE NOT NULL UNIQUE (one request per reservation), business_id UUID FK→businesses(id) ON DELETE CASCADE NOT NULL, customer_id UUID FK→customers(id) ON DELETE CASCADE NOT NULL, channel ENUM('sms','whatsapp') NOT NULL DEFAULT 'sms', platform ENUM('google','tripadvisor') NOT NULL DEFAULT 'google', review_url TEXT NOT NULL, short_code VARCHAR(12) UNIQUE NOT NULL, status ENUM('pending','sent','clicked','expired') NOT NULL DEFAULT 'pending', sent_at TIMESTAMPTZ nullable, clicked_at TIMESTAMPTZ nullable, expires_at TIMESTAMPTZ NOT NULL (sent_at + 30 days), created_at TIMESTAMPTZ. Indexes: `business_id+created_at` composite btree, `short_code` btree (redirect lookup), `status` btree. |
| `add_review_config_to_businesses_table`         | Add `review_requests_enabled BOOLEAN NOT NULL DEFAULT false`, `review_platform ENUM('google','tripadvisor') NOT NULL DEFAULT 'google'`, `review_delay_hours SMALLINT NOT NULL DEFAULT 2` (hours after `show` to send request), `google_place_id VARCHAR(255) nullable`, `tripadvisor_location_id VARCHAR(255) nullable`. |

---

#### 4.13.3 Back-end Tasks

| ID         | Task                                                                                                                                                                                                                                                                                                        | PRD Ref      |
|------------|-------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|--------------|
| P9-BE-001  | Create migration `add_crm_fields_to_customers_table` per Section 4.13.2.                                                                                                                                                                                                                                    | Conversation |
| P9-BE-002  | Create migration `create_review_requests_table` per Section 4.13.2.                                                                                                                                                                                                                                         | Conversation |
| P9-BE-003  | Create migration `add_review_config_to_businesses_table` per Section 4.13.2.                                                                                                                                                                                                                                | Conversation |
| P9-BE-004  | Update `app/Models/Customer.php` — add `notes`, `is_vip`, `is_blacklisted`, `birthday_month`, `birthday_day`, `preferred_table_notes` to `$fillable`; cast `is_vip` and `is_blacklisted` to bool; scope `vip(): Builder`; scope `blacklisted(): Builder`; scope `birthdayThisMonth(): Builder` filters birthday_month = current month.  | Conversation |
| P9-BE-005  | Create `app/Http/Requests/UpdateCustomerCrmRequest.php` — validate: `notes` sometimes\|string\|max:2000, `is_vip` sometimes\|boolean, `is_blacklisted` sometimes\|boolean, `birthday_month` sometimes\|integer\|min:1\|max:12\|nullable, `birthday_day` sometimes\|integer\|min:1\|max:31\|nullable, `preferred_table_notes` sometimes\|string\|max:255\|nullable; French messages.  | Conversation |
| P9-BE-006  | Create `app/Http/Controllers/Api/CustomerCrmController.php` — `update(UpdateCustomerCrmRequest $request, Customer $customer): JsonResponse` updates CRM fields on customer; authorises via `CustomerPolicy` (customer must belong to authenticated business's reservations); returns updated `CustomerResource`.  | Conversation |
| P9-BE-007  | Update `app/Http/Resources/CustomerResource.php` — add CRM fields to response: `notes`, `is_vip`, `is_blacklisted`, `birthday_month`, `birthday_day`, `preferred_table_notes`.  | Conversation |
| P9-BE-008  | Update `app/Http/Controllers/Api/ReservationController@store` — after customer lookup (by phone), include `is_blacklisted` in response payload: add `customer_blacklisted: bool` field to `ReservationResource` (or a dedicated response field); no blocking — blacklist is a warning only, not a hard stop.  | Conversation |
| P9-BE-009  | Create `app/Models/ReviewRequest.php` — `HasUuids`; `belongsTo(Reservation::class)`, `belongsTo(Business::class)`, `belongsTo(Customer::class)`; cast `status` to `ReviewRequestStatusEnum`; cast `platform` to `ReviewPlatformEnum`; scope `active(): Builder` filters status IN (pending, sent, clicked); no `updated_at` (insert-only + status update only).  | Conversation |
| P9-BE-010  | Create `app/Enums/ReviewRequestStatusEnum.php` — cases: `Pending`, `Sent`, `Clicked`, `Expired`; `app/Enums/ReviewPlatformEnum.php` — cases: `Google`, `Tripadvisor`.  | Conversation |
| P9-BE-011  | Create `app/Services/ReviewLinkService.php` — `buildGoogleReviewUrl(string $placeId): string` returns `https://search.google.com/local/writereview?placeid={placeId}`; `buildTripadvisorUrl(string $locationId): string` returns `https://www.tripadvisor.fr/UserReviewEdit-{locationId}`; `generateShortCode(): string` returns random 8-char alphanumeric unique code (checked against `review_requests.short_code`).  | Conversation |
| P9-BE-012  | Create `app/Services/ReviewRequestService.php` — `createAndSend(Reservation $reservation): ?ReviewRequest` checks `business->review_requests_enabled`; checks no existing request for this reservation (UNIQUE guard); builds review URL via `ReviewLinkService`; creates `ReviewRequest` with `expires_at = now() + 30 days`; dispatches `SendReviewRequestJob`; returns `ReviewRequest` or null if disabled.  | Conversation |
| P9-BE-013  | Create `app/Jobs/SendReviewRequestJob.php` — `handle()`: receives `ReviewRequest $reviewRequest`; validates status=pending (abort if already sent); sends SMS via `SmsServiceInterface` with message "Bonjour {name}, merci pour votre visite chez {businessName} ! Votre avis compte beaucoup : {shortUrl}"; `shortUrl = {APP_URL}/r/{short_code}`; updates `sent_at = now()`, `status = sent`; retries 3× on SMS failure.  | Conversation |
| P9-BE-014  | Create `app/Http/Controllers/Public/ReviewRedirectController.php` — `redirect(string $shortCode): RedirectResponse` finds `ReviewRequest` by `short_code` where status IN (sent, clicked) AND expires_at > now(); updates `clicked_at = now()`, `status = clicked`; redirects to `review_url`; returns 404 if not found or expired.  | Conversation |
| P9-BE-015  | Update `app/Observers/ReservationObserver.php` — in `updated()`: when status transitions to `show`, dispatch `SendReviewRequestJob` via `ReviewRequestService::createAndSend()` with delay of `business->review_delay_hours * 3600` seconds.  | Conversation |
| P9-BE-016  | Create `app/Http/Resources/ReviewRequestResource.php` — expose: id, reservation_id, platform, status, sent_at, clicked_at, short_code (masked as `{APP_URL}/r/{code}`), expires_at.  | Conversation |
| P9-BE-017  | Create `app/Http/Controllers/Api/ReviewRequestController.php` — `index(Request $request): JsonResponse` returns paginated `ReviewRequestResource[]` for authenticated business; filterable by `?status`, `?platform`, `?date_from`, `?date_to`; `stats(): JsonResponse` returns `{total_sent, total_clicked, click_rate_percent}` for last 30 days.  | Conversation |

---

#### 4.13.4 Back-end Tests (TDD)

| Test File                                                           | Test Cases                                                                                                                                                                                                                                   |
|---------------------------------------------------------------------|----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `tests/Feature/Crm/CustomerCrmControllerTest.php`                  | update sets notes on customer, update sets is_vip=true, update sets is_blacklisted=true, update clears birthday fields when null, update returns 403 for customer not belonging to business, update returns 422 on invalid birthday_month    |
| `tests/Feature/Crm/BlacklistWarningTest.php`                       | store reservation response includes customer_blacklisted=true for blacklisted phone, store includes customer_blacklisted=false for non-blacklisted, store includes customer_blacklisted=false for new customer (no history)                    |
| `tests/Unit/Services/ReviewLinkServiceTest.php`                    | buildGoogleReviewUrl returns correct URL with placeId, buildTripadvisorUrl returns correct URL with locationId, generateShortCode returns 8-char alphanumeric string, generateShortCode is unique across existing codes                      |
| `tests/Unit/Services/ReviewRequestServiceTest.php`                 | createAndSend returns null when review_requests_enabled=false, createAndSend creates ReviewRequest and dispatches job, createAndSend returns null when request already exists for reservation (UNIQUE), createAndSend uses correct delay from business config |
| `tests/Unit/Jobs/SendReviewRequestJobTest.php`                     | sends SMS with correct short URL, updates status to sent and sets sent_at, aborts if status is already sent (idempotency), retries on SMS service exception                                                                                  |
| `tests/Feature/Review/ReviewRedirectControllerTest.php`            | valid short_code redirects to review_url and sets clicked_at, already-clicked code still redirects and does not overwrite clicked_at, expired code returns 404, unknown code returns 404                                                    |
| `tests/Feature/Review/ReservationObserverReviewTest.php`           | dispatches SendReviewRequestJob when status changes to show and review_requests_enabled=true, does not dispatch when review_requests_enabled=false, does not dispatch when status change is not to show                                       |
| `tests/Feature/Review/ReviewRequestControllerTest.php`             | index returns paginated review requests for authenticated business, index filters by status, stats returns correct total_sent and click_rate_percent                                                                                          |

---

#### 4.13.5 Front-end Tasks

| ID         | Task                                                                                                                                                                                                                                             | PRD Ref      |
|------------|--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|--------------|
| P9-FE-001  | Create `src/api/crm.ts` — typed client: `updateCustomerCrm(customerId: string, payload: UpdateCrmPayload): Promise<Customer>`, `getReviewRequests(params: ReviewFilter): Promise<PaginatedResponse<ReviewRequest>>`, `getReviewStats(): Promise<ReviewStats>`; define `UpdateCrmPayload`, `ReviewRequest`, `ReviewStats` interfaces.  | Conversation |
| P9-FE-002  | Create `src/components/crm/CustomerCrmPanel.vue` — expandable side panel or modal: notes textarea (auto-save on blur), VIP toggle (star icon, yellow when active), blacklist toggle (ban icon, red when active), birthday month/day pickers, preferred table notes input; shows "Sauvegardé" confirmation after each save; Props: `customer: Customer`; Emits: `updated`.  | Conversation |
| P9-FE-003  | Create `src/components/crm/CustomerVipBadge.vue` — small inline badge: gold star + "VIP" shown on customer name when `is_vip=true`; Props: `isVip: boolean`.  | Conversation |
| P9-FE-004  | Create `src/components/crm/BlacklistWarningBanner.vue` — red alert banner: "⚠️ Ce client est sur liste noire. Vérifiez avant de confirmer la réservation." Shown on `ReservationDetailPage` when `customer_blacklisted=true`; dismissible; Props: `visible: boolean`.  | Conversation |
| P9-FE-005  | Update `src/pages/ReservationDetailPage.vue` — show `BlacklistWarningBanner` when `reservation.customer_blacklisted=true`; add "Fiche client" button that opens `CustomerCrmPanel`.  | Conversation |
| P9-FE-006  | Update existing customer list page (or create `src/views/CustomersView.vue` if not existing) — add filter chips: "VIP", "Liste noire", "Anniversaire ce mois"; render `CustomerVipBadge` inline; add CRM panel access per row.  | Conversation |

---

#### 4.13.6 Front-end Tests

| Test File                                                              | Test Cases                                                                                                                                                                               |
|------------------------------------------------------------------------|------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `src/components/crm/__tests__/CustomerCrmPanel.test.ts`               | renders notes textarea with existing value, VIP toggle calls updateCustomerCrm with is_vip=true, blacklist toggle calls API with is_blacklisted=true, shows Sauvegardé after successful save, shows error message on API failure |
| `src/components/crm/__tests__/BlacklistWarningBanner.test.ts`         | renders warning text when visible=true, does not render when visible=false, dismiss button hides banner                                                                                  |
| `src/components/crm/__tests__/CustomerVipBadge.test.ts`               | renders star and VIP text when isVip=true, renders nothing when isVip=false                                                                                                              |

---

#### 4.13.7 DevOps / Infrastructure Tasks

| ID         | Task                                                                                                                                             | PRD Ref      |
|------------|--------------------------------------------------------------------------------------------------------------------------------------------------|--------------|
| P9-DO-001  | Add CRM and review routes to `routes/api.php` — `PATCH /api/v1/customers/{id}/crm`, `GET /api/v1/review-requests`, `GET /api/v1/review-requests/stats`; all `auth:sanctum`.  | Conversation |
| P9-DO-002  | Add public redirect route to `routes/web.php` — `GET /r/{shortCode}` → `ReviewRedirectController@redirect`; no auth; rate limit 30/minute per IP.  | Conversation |

---

#### 4.13.8 Deliverables Checklist

- [ ] CRM fields migrated on `customers` table (notes, VIP, blacklist, birthday, preferred table)
- [ ] `CustomerCrmController` update endpoint tested and returning updated `CustomerResource`
- [ ] Blacklist flag returned in reservation creation response
- [ ] `BlacklistWarningBanner` shown in reservation detail when customer is blacklisted
- [ ] `review_requests` table and review config columns on businesses migrated
- [ ] `SendReviewRequestJob` sends SMS with correct short URL
- [ ] `ReviewRedirectController` tracks clicks and redirects
- [ ] `ReservationObserver` dispatches review request on `show` status
- [ ] All Sprint 13 backend + frontend tests passing

---

### 4.14 Sprint 14 — Reputation Settings, Dashboard & Polish (Weeks 25–26)

#### 4.14.1 Sprint Objectives

- Review settings page allows business owner to configure platform, delay, Google Place ID/TripAdvisor ID
- Review request dashboard shows sent/clicked stats per month
- Customer list filters (VIP, blacklist, birthday) fully operational
- Full CI green, coverage ≥ 80%

---

#### 4.14.2 Database Migrations

No new migrations in Sprint 14.

---

#### 4.14.3 Back-end Tasks

| ID         | Task                                                                                                                                                                                                                                             | PRD Ref      |
|------------|--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|--------------|
| P9-BE-020  | Create `app/Http/Controllers/Api/ReviewSettingsController.php` — `show(Request $request): JsonResponse` returns review configuration for authenticated business; `update(ReviewSettingsRequest $request): JsonResponse` updates `review_requests_enabled`, `review_platform`, `review_delay_hours`, `google_place_id`, `tripadvisor_location_id`.  | Conversation |
| P9-BE-021  | Create `app/Http/Requests/ReviewSettingsRequest.php` — validate: `review_requests_enabled` required\|boolean, `review_platform` required\|in:google,tripadvisor, `review_delay_hours` required\|integer\|min:0\|max:48, `google_place_id` sometimes\|string\|max:255\|nullable (required when platform=google and enabled=true), `tripadvisor_location_id` sometimes\|string\|max:255\|nullable (required when platform=tripadvisor and enabled=true); French messages; custom rule: platform-specific ID must be present when enabled.  | Conversation |
| P9-BE-022  | Create `app/Http/Resources/ReviewSettingsResource.php` — expose: review_requests_enabled, review_platform, review_delay_hours, google_place_id (masked: show only if set, else null), tripadvisor_location_id (masked).  | Conversation |
| P9-BE-023  | Create `app/Console/Commands/ExpireReviewRequests.php` — `review-requests:expire` marks `ReviewRequest` rows with `status=sent AND expires_at < now()` as `expired`; chunks by 500; logs count; register in `Kernel.php` to run `daily()`.  | Conversation |
| P9-BE-024  | Update `app/Http/Controllers/Api/CustomerController@index` (if exists, or create) — add query params `?filter[is_vip]=1`, `?filter[is_blacklisted]=1`, `?filter[birthday_month]=<month>` to existing customer listing endpoint; return `CustomerResource` with CRM fields included.  | Conversation |

---

#### 4.14.4 Back-end Tests (TDD)

| Test File                                                            | Test Cases                                                                                                                                                                              |
|----------------------------------------------------------------------|-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `tests/Feature/Review/ReviewSettingsControllerTest.php`             | show returns current settings, update enables review requests with Google platform, update requires google_place_id when platform=google and enabled=true, update returns 422 when place_id missing, update saves tripadvisor_location_id for tripadvisor platform |
| `tests/Unit/Commands/ExpireReviewRequestsCommandTest.php`           | marks sent requests with past expires_at as expired, preserves clicked requests, preserves requests with future expires_at, outputs count of expired records                             |
| `tests/Feature/Crm/CustomerFilterTest.php`                          | index with filter[is_vip]=1 returns only VIP customers, index with filter[is_blacklisted]=1 returns only blacklisted, index with filter[birthday_month]=3 returns customers with birthday in March |

---

#### 4.14.5 Front-end Tasks

| ID         | Task                                                                                                                                                                                                                                                                     | PRD Ref      |
|------------|--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|--------------|
| P9-FE-020  | Create `src/views/ReputationView.vue` — page at `/reputation` route (requiresAuth); two sections: "Paramètres avis" (review settings card) and "Historique des demandes" (review request list with stats bar); add route + sidebar nav link (star icon).  | Conversation |
| P9-FE-021  | Create `src/components/reputation/ReviewSettingsCard.vue` — form: enable toggle, platform radio (Google / TripAdvisor), delay picker (0h, 1h, 2h, 4h, 8h, 24h, 48h), Google Place ID input (shown when Google selected) with "Comment trouver mon Place ID" accordion, TripAdvisor ID input (shown when TripAdvisor selected); save button; inline validation.  | Conversation |
| P9-FE-022  | Create `src/components/reputation/ReviewStatsBar.vue` — 3 KPI cards: "Demandes envoyées", "Avis cliqués", "Taux de clic"; values from `getReviewStats()`; Props: `stats: ReviewStats`.  | Conversation |
| P9-FE-023  | Create `src/components/reputation/ReviewRequestTable.vue` — table: customer name, date, platform badge (Google/TripAdvisor), status badge (sent/clicked/expired), sent_at, clicked_at; empty state "Aucune demande envoyée"; Props: `requests: ReviewRequest[]`.  | Conversation |
| P9-FE-024  | Create `src/components/reputation/ReviewPlatformBadge.vue` — pill: Google (blue, G icon), TripAdvisor (green, leaf icon); Props: `platform: ReviewPlatform`.  | Conversation |

---

#### 4.14.6 Front-end Tests

| Test File                                                                | Test Cases                                                                                                                                                                      |
|--------------------------------------------------------------------------|---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `src/components/reputation/__tests__/ReviewSettingsCard.test.ts`        | renders toggle and platform radio, Google Place ID field shown when Google selected, TripAdvisor ID field shown when TripAdvisor selected, save calls updateReviewSettings, shows validation error when enabled=true and no place_id |
| `src/components/reputation/__tests__/ReviewStatsBar.test.ts`            | renders sent count, clicked count, and click rate percentage formatted correctly                                                                                                |
| `src/components/reputation/__tests__/ReviewRequestTable.test.ts`        | renders rows with correct status badges, shows empty state when array is empty, platform badge renders correct label for google and tripadvisor                                |

---

#### 4.14.7 DevOps / Infrastructure Tasks

| ID         | Task                                                                                                                                       | PRD Ref      |
|------------|--------------------------------------------------------------------------------------------------------------------------------------------|--------------|
| P9-DO-020  | Add review settings routes to `routes/api.php` — `GET/PATCH /api/v1/review-settings`; `auth:sanctum`.  | Conversation |
| P9-DO-021  | Add customer CRM filter support documentation to `docs/dev/phase9.md` Audit Notes; note that existing customer index endpoint is extended rather than replaced. | Conversation |

---

#### 4.14.8 Deliverables Checklist

- [ ] `ReviewSettingsController` CRUD with platform-specific ID validation
- [ ] `ReviewSettingsCard` allows enabling, configuring platform and delay
- [ ] `ReputationView` page at `/reputation` with stats + table
- [ ] Customer list VIP/blacklist/birthday filters operational in dashboard
- [ ] `ExpireReviewRequests` command scheduled daily
- [ ] All Sprint 14 backend + frontend tests passing
- [ ] Backend coverage ≥ 80%, frontend ≥ 80%
- [ ] CI pipeline green on `main`

---

## Section 5 — API Endpoints Delivered in Phase 9

| Method | Endpoint                              | Controller                              | Auth    | Notes                                                                                                              |
|--------|---------------------------------------|-----------------------------------------|---------|--------------------------------------------------------------------------------------------------------------------|
| PATCH  | `/api/v1/customers/{id}/crm`          | `CustomerCrmController@update`          | Bearer  | Body: `{notes?, is_vip?, is_blacklisted?, birthday_month?, birthday_day?, preferred_table_notes?}`. Returns `CustomerResource`. |
| GET    | `/api/v1/review-requests`             | `ReviewRequestController@index`         | Bearer  | Returns paginated `ReviewRequestResource[]`. Accepts `?status`, `?platform`, `?date_from`, `?date_to`.            |
| GET    | `/api/v1/review-requests/stats`       | `ReviewRequestController@stats`         | Bearer  | Returns `{total_sent, total_clicked, click_rate_percent}` for last 30 days.                                       |
| GET    | `/api/v1/review-settings`             | `ReviewSettingsController@show`         | Bearer  | Returns current review configuration for authenticated business.                                                   |
| PATCH  | `/api/v1/review-settings`             | `ReviewSettingsController@update`       | Bearer  | Body: `{review_requests_enabled, review_platform, review_delay_hours, google_place_id?, tripadvisor_location_id?}`. |
| GET    | `/r/{shortCode}`                      | `ReviewRedirectController@redirect`     | No      | Tracks click, redirects to review platform URL. Returns 404 for expired/unknown codes.                            |

---

## Section 6 — Exit Criteria

| #  | Criterion                                                                                                    | Validated |
|----|--------------------------------------------------------------------------------------------------------------|-----------|
| 1  | All P9 functional requirements implemented: CRM fields, blacklist warning, review requests, redirect tracking | [ ]      |
| 2  | Backend test coverage ≥ 80%                                                                                  | [ ]       |
| 3  | Frontend test coverage ≥ 80%                                                                                 | [ ]       |
| 4  | Pint passes with zero errors                                                                                  | [ ]       |
| 5  | PHPStan level 8 passes with zero errors                                                                      | [ ]       |
| 6  | ESLint + Prettier passes with zero errors                                                                     | [ ]       |
| 7  | All Pest tests pass                                                                                           | [ ]       |
| 8  | All Vitest tests pass                                                                                         | [ ]       |
| 9  | CI pipeline green on `main`                                                                                   | [ ]       |
| 10 | Staging: reservation marked as show → SMS received after configured delay → link redirects to Google/TripAdvisor | [ ]  |
| 11 | Staging: click tracked in dashboard review request table                                                      | [ ]       |
| 12 | Staging: blacklisted customer triggers warning banner in reservation creation                                 | [ ]       |
| 13 | `docs/dev/phase9.md` fully updated                                                                           | [ ]       |

---

## Section 7 — Risks Specific to Phase 9

| Risk                                                              | Probability | Impact | Mitigation                                                                                                        |
|-------------------------------------------------------------------|-------------|--------|-------------------------------------------------------------------------------------------------------------------|
| Google Place ID incorrect → review link 404                       | High        | Medium | Validate format in backend (must start with "ChIJ"); add "Tester le lien" button in dashboard before enabling.  |
| Google ToS prohibit incentivised review requests                  | Low         | Medium | ZeroNoShow does not offer incentives; plain "votre avis compte" framing is compliant.                            |
| RGPD: SMS sent without explicit review-request consent            | Medium      | High   | Review request opt-out must be documented; add unsubscribe link in SMS body (`/r/{code}/stop`).                  |
| ReservationObserver chain grows too complex                       | Medium      | Medium | Extract observer logic to dedicated `ReservationEventService`; keep observer as thin dispatcher only.            |

---

## Section 8 — External Dependencies

| Service / Library   | Phase 9 Usage                                            | Fallback if Unavailable                                 |
|---------------------|----------------------------------------------------------|---------------------------------------------------------|
| Twilio SMS          | Review request delivery (same as existing reminders)     | Queue silently; retry on next run                       |
| Google Maps Places  | Deep-link construction using `place_id`                  | Link to business website if no place_id configured      |
| TripAdvisor         | Deep-link construction using `location_id`               | Link to TripAdvisor search page for business name       |

---

## Assumptions

- Review requests use the existing Twilio SMS service (Phase 1). WhatsApp review requests are deferred.
- The `short_code` redirect URL is hosted on ZeroNoShow's own domain (`/r/{code}`), not a third-party shortener.
- `google_place_id` is a free-text field; validation checks format (starts with "ChIJ") but does not verify the ID against the Google Places API (no API key required in Phase 9).
- A maximum of one review request is sent per reservation (UNIQUE constraint on `reservation_id`). If the request bounces, it is not retried.
- Blacklist is a **warning only** — the business owner makes the final decision whether to accept the reservation.
- Birthday-based SMS campaigns are explicitly deferred due to RGPD marketing consent requirements.
