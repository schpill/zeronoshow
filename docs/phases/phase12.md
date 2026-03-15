# Phase 12 — ZeroNoShow Operator Admin Backoffice

| Field            | Value                                                                    |
|------------------|--------------------------------------------------------------------------|
| **Phase**        | 12 of 12                                                                 |
| **Name**         | Operator Admin Backoffice — Business Management, Monitoring & Swagger    |
| **Duration**     | Weeks 35–38 (4 weeks)                                                    |
| **Milestone**    | M12 — Operator can manage all businesses and monitor platform health     |
| **PRD Sections** | §10 (Security), §11 (API Specification), §12 (Success Criteria)          |
| **Prerequisite** | Phase 11 fully completed and validated                                   |
| **Status**       | Not started                                                              |

---

## Section 1 — Phase Objectives

| ID        | Objective                                                                                                                    |
|-----------|------------------------------------------------------------------------------------------------------------------------------|
| P12-OBJ-1 | Operator (Gerald) can log in to a protected `/admin` backoffice via a separate admin account (not a business account)        |
| P12-OBJ-2 | Admin dashboard shows platform KPIs: total businesses, trials active, paid subscriptions, total SMS sent this month          |
| P12-OBJ-3 | Admin can list, search, and view any business — including their reservations, SMS logs, subscription status, and trial state  |
| P12-OBJ-4 | Admin can perform manual interventions: extend a business trial by N days, cancel a subscription, impersonate a business     |
| P12-OBJ-5 | A complete Swagger UI is accessible at `/api/docs` (L5-Swagger), covering all 40+ API endpoints across phases 1–12          |
| P12-OBJ-6 | `make routes` displays the full route list, `make swagger` regenerates the OpenAPI JSON                                      |
| P12-OBJ-7 | System health panel shows: queue worker status, failed jobs count, Redis connectivity, recent Twilio webhook deliveries      |
| P12-OBJ-8 | Admin actions are logged in an `admin_audit_logs` table (who did what, when, on which resource)                              |

---

## Section 2 — Entry Criteria

- Phase 11 (In-App Help & Onboarding) merged and CI green on `main`
- `businesses`, `reservations`, `sms_logs`, `customers` tables stable and at final schema
- Stripe billing working (`subscription_status` on `businesses` managed via existing flow)
- Redis + queue workers running (established Phase 1–4)
- Laravel Sanctum installed and configured (existing)
- `darkaonline/l5-swagger` compatible with Laravel 12 (verify before starting P12-BE-001)

---

## Section 3 — Scope — Requirement Traceability

| Requirement Group                          | ID Range        | Status   | Notes                                                                       |
|--------------------------------------------|-----------------|----------|-----------------------------------------------------------------------------|
| Admin authentication (separate from biz)   | §10 Security    | Included | Separate `admins` table + Sanctum token with `admin` ability                |
| Platform KPI dashboard                     | §12 Success     | Included | Total businesses, active trials, paid subs, SMS sent this month             |
| Business listing + search + detail view    | §12 Success     | Included | Searchable by name/email, filterable by subscription status                 |
| Trial extension (manual)                   | §12 Success     | Included | `PATCH /api/v1/admin/businesses/{id}/extend-trial`                          |
| Subscription cancellation (manual)         | §12 Success     | Included | `PATCH /api/v1/admin/businesses/{id}/cancel-subscription`                   |
| Business impersonation                     | §12 Success     | Included | Issue short-lived token (15min TTL) that allows browsing as a business      |
| Admin audit log                            | §10 Security    | Included | Append-only log of all admin write actions                                  |
| Swagger UI (`/api/docs`)                   | §11 API Spec    | Included | L5-Swagger annotations on all controllers; `make swagger` regenerates JSON  |
| System health panel                        | §12 QI          | Included | Queue worker, failed jobs, Redis ping, last webhook received                 |
| Role-based access control (multi-admin)    | —               | No       | Single admin user for MVP; multi-admin with roles deferred                  |
| Admin mobile app                           | —               | No       | Web-only                                                                    |
| Automated billing management (Stripe)      | —               | Partial  | View subscription status; direct Stripe mutations via Stripe dashboard only |

---

## Section 4 — Detailed Sprint Breakdown

### 4.19 Sprint 19 — Admin API: Auth, Business Management & Audit (Weeks 35–36)

#### 4.19.1 Sprint Objectives

- Admin can authenticate via `POST /api/v1/admin/login` and receive a Sanctum token with `admin` ability
- All admin routes protected by `auth:sanctum` + `ability:admin` middleware
- Full business management CRUD (list, search, show, extend-trial, cancel-subscription) operational
- Impersonation endpoint issues a short-lived business token
- All admin write actions logged to `admin_audit_logs`
- OpenAPI annotations added to all Phase 1–10 controllers (retroactive), Swagger JSON generates cleanly

#### 4.19.2 Database Migrations

| Migration name                       | Description                                                                                                                                                                                                                                       |
|--------------------------------------|---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `create_admins_table`                | `id` UUID PK (HasUuids), `name` VARCHAR(255) NOT NULL, `email` VARCHAR(255) UNIQUE NOT NULL, `password` VARCHAR(255) NOT NULL (bcrypt), `remember_token` VARCHAR(100) nullable, `created_at` TIMESTAMPTZ NOT NULL DEFAULT now(), `updated_at` TIMESTAMPTZ NOT NULL DEFAULT now(). Index: `email` btree UNIQUE. |
| `create_admin_audit_logs_table`      | `id` UUID PK, `admin_id` UUID FK → `admins(id)` ON DELETE SET NULL nullable (preserve log if admin deleted), `action` VARCHAR(100) NOT NULL (e.g., `extend_trial`, `cancel_subscription`, `impersonate`), `target_type` VARCHAR(50) NOT NULL (e.g., `Business`), `target_id` UUID NOT NULL, `payload` JSONB nullable (before/after values), `ip_address` VARCHAR(45), `created_at` TIMESTAMPTZ NOT NULL DEFAULT now(). Indexes: `admin_id` btree, `target_type + target_id` composite btree, `created_at` btree. No `updated_at` (append-only). |

#### 4.19.3 Back-end Tasks

| ID         | Task                                                                                                                                                                                                                                                                                            | PRD Ref     |
|------------|-------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|-------------|
| P12-BE-001 | Install `darkaonline/l5-swagger` via Composer. Publish config (`config/l5-swagger.php`). Configure: `api.host = env('APP_URL')`, `generate_always = env('L5_SWAGGER_GENERATE_ALWAYS', false)`, `paths.docs = storage_path('api-docs')`, security definition `bearerAuth`. Run `php artisan l5-swagger:generate` to verify zero errors. | §11         |
| P12-BE-002 | Create `Admin` model — `app/Models/Admin.php` — extends `Authenticatable`, implements `HasApiTokens` (Sanctum), uses `HasUuids`. Fillable: `name`, `email`, `password`. Hidden: `password`, `remember_token`. Cast `password` to `hashed`. Add to Sanctum `guards` config. | §10         |
| P12-BE-003 | Create `AdminLoginRequest` — validate: `email` (required, email), `password` (required, string). Create `AdminAuthController::login(AdminLoginRequest): JsonResponse` — verify credentials with `Auth::guard('admin')`, issue Sanctum token with ability `admin` (TTL: 8 hours, stored in `personal_access_tokens` with `tokenable_type = Admin`). Return `{token, admin: {id, name, email}}`. On failure: 401 after 5 attempts → Redis lock `admin:lockout:{email}` for 15 minutes. | §10         |
| P12-BE-004 | Create `AdminAuthController::logout(Request): JsonResponse` — revoke current token via `$request->user()->currentAccessToken()->delete()`. Return 204. | §10         |
| P12-BE-005 | Create `EnsureAdminAbility` middleware — `app/Http/Middleware/EnsureAdminAbility.php` — checks `$request->user()->tokenCan('admin')` and that `$request->user()` is an `Admin` instance. Returns 403 if not. Register in `bootstrap/app.php` as alias `admin.ability`. | §10         |
| P12-BE-006 | Register admin route group in `routes/api.php` — prefix `/v1/admin`, middleware `['auth:sanctum', 'admin.ability']`. All admin endpoints defined within this group. | §10         |
| P12-BE-007 | Create `AdminDashboardController::stats(): JsonResponse` — returns: `total_businesses`, `active_trials` (subscription_status = 'trial' AND trial_ends_at > now()), `expired_trials` (trial_ends_at ≤ now() AND subscription_status = 'trial'), `paid_subscriptions` (subscription_status = 'active'), `cancelled_subscriptions`, `sms_sent_this_month` (count from `sms_logs` WHERE `created_at >= first day of current month`), `sms_cost_this_month` (sum of `cost_eur`), `failed_jobs_count` (from `failed_jobs` table). All counts via single aggregate query. | §12         |
| P12-BE-008 | Create `AdminBusinessController::index(Request): JsonResponse` — paginated list (20/page) of businesses. Filters: `?search=` (name OR email ILIKE), `?status=trial\|active\|cancelled`. Sorts: `?sort=created_at\|name\|subscription_status` (default `created_at DESC`). Returns `AdminBusinessResource` (extends `BusinessResource` + adds: `reservations_count`, `sms_sent_count`, `last_reservation_at`). | §12         |
| P12-BE-009 | Create `AdminBusinessController::show(Business): JsonResponse` — returns full business detail: business fields, 10 most recent reservations (via `ReservationResource`), SMS log summary (total sent, delivered, failed, cost), subscription history. | §12         |
| P12-BE-010 | Create `AdminBusinessController::extendTrial(Request, Business): JsonResponse` — validate: `days` (required, integer, 1–90). Logic: if `subscription_status !== 'trial'` → 422 "Business is not in trial". Extend `trial_ends_at` by `$days` days. Log to `admin_audit_logs` (`extend_trial`, payload: `{days, old_trial_ends_at, new_trial_ends_at}`). Return updated `AdminBusinessResource`. | §12         |
| P12-BE-011 | Create `AdminBusinessController::cancelSubscription(Request, Business): JsonResponse` — validate: `reason` (required, string, max 500). Logic: set `subscription_status = 'cancelled'`. Log to `admin_audit_logs` (`cancel_subscription`, payload: `{reason}`). Does NOT call Stripe — Stripe cancellation is done separately in Stripe dashboard. Return updated `AdminBusinessResource`. | §12         |
| P12-BE-012 | Create `AdminBusinessController::impersonate(Business): JsonResponse` — issue a short-lived Sanctum token (15 min TTL) for the business owner with ability `impersonate`. Return `{impersonation_token, business_name, expires_at}`. Log to `admin_audit_logs` (`impersonate`, payload: `{business_id, business_name}`). Frontend uses this token as `Authorization: Bearer` to browse the business's dashboard. | §12         |
| P12-BE-013 | Create `AdminAuditLog` model — `app/Models/AdminAuditLog.php` — `HasUuids`, no `updated_at` (set `const UPDATED_AT = null`). Create `AdminAuditLogResource`. Create `AdminAuditController::index(Request): JsonResponse` — paginated list (50/page), filterable by `?admin_id=`, `?target_type=`, `?action=`, sortable by `created_at DESC`. | §10         |
| P12-BE-014 | Create `AdminSystemController::health(): JsonResponse` — returns: `queue_worker_running` (check Redis key `znz:worker:heartbeat` set by scheduler every 30s), `failed_jobs_count` (SELECT COUNT(*) FROM failed_jobs), `redis_ping` (try Redis::ping()), `last_twilio_webhook_at` (max `created_at` from `sms_logs` where `provider_id IS NOT NULL`), `database_ok` (try DB::select('SELECT 1')). Returns 200 with all fields; frontend uses status colors. | §12 QI      |
| P12-BE-015 | Add OpenAPI `@OA\Info` annotation to `app/Http/Controllers/Controller.php` — title: "ZeroNoShow API", version: "1.0.0", description, contact. Add `@OA\SecurityScheme` for `bearerAuth`. | §11         |
| P12-BE-016 | Add `@OA\Tag` and `@OA\Get/@OA\Post/@OA\Patch/@OA\Delete` annotations with `@OA\Response` to `AuthController`, `ReservationController`, `DashboardController` (Phase 1–3 controllers). | §11         |
| P12-BE-017 | Add Swagger annotations to `WaitlistController`, `BookingWidgetController`, `CustomerController`, `ReputationController`, `LeoController`, `VoiceController`, `SubscriptionController` (Phase 5–10 controllers). | §11         |
| P12-BE-018 | Add Swagger annotations to all Phase 12 admin controllers (`AdminAuthController`, `AdminDashboardController`, `AdminBusinessController`, `AdminAuditController`, `AdminSystemController`). | §11         |
| P12-BE-019 | Add `CreateAdminSeeder` — creates admin account from env `ADMIN_EMAIL` + `ADMIN_PASSWORD`. Run via `php artisan db:seed --class=CreateAdminSeeder`. Add to `DatabaseSeeder` behind `app()->environment('local', 'staging')` guard. | §10         |

#### 4.19.4 Back-end Tests (TDD)

| Test File                                                            | Test Cases                                                                                                                                                          |
|----------------------------------------------------------------------|---------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `tests/Feature/Admin/AdminAuthTest.php`                              | Login with valid credentials returns token, invalid password → 401, lockout after 5 attempts → Redis key set, logout revokes token, unauthenticated admin route → 401 |
| `tests/Feature/Admin/AdminDashboardTest.php`                         | Stats endpoint returns all expected fields, `sms_sent_this_month` counts only current month, `failed_jobs_count` reflects actual count                               |
| `tests/Feature/Admin/AdminBusinessTest.php`                          | Index returns paginated list, search filters by name, search filters by email, status filter works, show returns reservations + SMS summary, extend-trial adds days, extend-trial on paid business → 422, cancel-subscription sets status + logs audit, impersonate returns token with 15min TTL |
| `tests/Feature/Admin/AdminAuditLogTest.php`                          | All write actions (extend-trial, cancel, impersonate) create audit log entry, audit log index paginated, filter by action works, filter by target_type works         |
| `tests/Feature/Admin/AdminSystemTest.php`                            | Health endpoint returns all fields, Redis ping failure reported gracefully (mock Redis unavailable)                                                                  |
| `tests/Unit/Models/AdminTest.php`                                    | Admin model uses HasUuids, password is cast to hashed, tokenCan('admin') works                                                                                      |

#### 4.19.5 DevOps / Infrastructure Tasks

| ID         | Task                                                                                                                                                                      | PRD Ref |
|------------|---------------------------------------------------------------------------------------------------------------------------------------------------------------------------|---------|
| P12-DO-001 | Add `ADMIN_EMAIL` and `ADMIN_PASSWORD` to `.env` (local) and document in `docker-compose.yml` environment block for `api` service. Never commit real credentials.        | §10     |
| P12-DO-002 | Add `L5_SWAGGER_GENERATE_ALWAYS=true` to `.env` for local dev. Set `false` in production (use `make swagger` explicitly). Document in Makefile comment.                  | §11     |
| P12-DO-003 | Add `znz:worker:heartbeat` Redis key set in scheduler — add 30-second heartbeat in `app/Console/Kernel.php` (or `bootstrap/app.php` schedule): `schedule->call(fn() => Redis::setex('znz:worker:heartbeat', 60, now()))->everyMinute()`. | §12 QI  |

#### 4.19.6 Deliverables Checklist

- [ ] `POST /api/v1/admin/login` returns token, lockout after 5 failed attempts
- [ ] All admin routes return 403 for non-admin tokens
- [ ] Business list, search, filter, show all return correct data
- [ ] Extend-trial persists new `trial_ends_at` and logs audit entry
- [ ] Cancel-subscription sets status + logs audit entry
- [ ] Impersonate returns token with 15-minute TTL
- [ ] `admin_audit_logs` populated for all write operations
- [ ] `GET /api/v1/admin/system/health` returns all expected fields
- [ ] `php artisan l5-swagger:generate` completes without errors
- [ ] All Sprint 19 Pest tests passing

---

### 4.20 Sprint 20 — Admin Frontend & Swagger UI (Weeks 37–38)

#### 4.20.1 Sprint Objectives

- `/admin/login` renders a login form and stores admin token in a separate Pinia store
- `/admin/dashboard` shows platform KPIs and system health panel
- `/admin/businesses` shows the paginated, searchable business list
- `/admin/businesses/:id` shows full business detail with intervention controls
- `/admin/audit` shows the paginated audit log
- Swagger UI accessible at `/api/docs` (L5-Swagger serves the JSON; frontend deep-links to it)
- Admin frontend is completely isolated from the business frontend (separate routes, separate Pinia store, no shared auth state)

#### 4.20.2 Database Migrations

*(No schema changes in Sprint 20.)*

#### 4.20.3 Front-end Tasks

| ID         | Task                                                                                                                                                                                                                                                         | PRD Ref  |
|------------|--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|----------|
| P12-FE-020 | Add admin routes to `src/router/index.ts` — `/admin/login` (guestOnlyAdmin), `/admin` parent with `AdminLayout.vue` (requiresAdmin), children: `/admin/dashboard`, `/admin/businesses`, `/admin/businesses/:id`, `/admin/audit`. Use `requiresAdmin` meta guard (check `adminStore.isAuthenticated`). | §10      |
| P12-FE-021 | Create `src/stores/admin.ts` (Pinia) — state: `token: string \| null`, `admin: { id, name, email } \| null`. Actions: `login(email, password)` → POST `/api/v1/admin/login`, store token in `localStorage` key `znz_admin_token`. `logout()` → POST `/api/v1/admin/logout`, clear state + storage. `fetchMe()` → GET `/api/v1/admin/me`. Computed: `isAuthenticated: boolean`. Separate from `useAuthStore` — no cross-contamination. | §10      |
| P12-FE-022 | Create `src/api/adminAxios.ts` — Axios instance with `baseURL = /api/v1/admin`, interceptor reads `znz_admin_token` from `localStorage` for `Authorization: Bearer` header. Separate from the business `axios.ts`. 401 → redirect to `/admin/login`. | §10      |
| P12-FE-023 | Create `src/layouts/AdminLayout.vue` — top nav with: "ZeroNoShow Admin", links to Dashboard / Businesses / Audit, username display, logout button. No `NavBar`, no `TrialBanner`. Sidebar on desktop, hamburger on mobile. Dark header (slate-900). | §10      |
| P12-FE-024 | Create `src/pages/admin/AdminLoginPage.vue` — login form: email + password inputs, submit button with spinner. On success: calls `adminStore.login()`, redirects to `/admin/dashboard`. On error 401: shows "Identifiants invalides". On lockout (429): shows "Trop de tentatives, réessayez dans 15 minutes". | §10      |
| P12-FE-025 | Create `src/pages/admin/AdminDashboardPage.vue` — two sections: (1) KPI grid (6 stat cards: total businesses, active trials, expired trials, paid, total SMS this month, SMS cost this month); (2) System health panel (queue worker status with colored dot, failed jobs count with link to `/admin/businesses`, Redis status, last webhook date). Polls `GET /api/v1/admin/system/health` every 30 seconds. | §12      |
| P12-FE-026 | Create `src/components/admin/StatCard.vue` — props: `label: string`, `value: string \| number`, `color?: 'green'\|'yellow'\|'red'\|'blue'` (default `blue`). Renders a bordered card with label + large value + optional color accent. Used in `AdminDashboardPage`. | §12      |
| P12-FE-027 | Create `src/components/admin/HealthIndicator.vue` — props: `status: 'ok'\|'warning'\|'error'`, `label: string`, `detail?: string`. Renders colored dot (green/yellow/red) + label + optional detail text. Used in system health section. | §12 QI   |
| P12-FE-028 | Create `src/pages/admin/AdminBusinessListPage.vue` — paginated table of businesses. Columns: name, email, subscription status (badge), trial ends at, reservations count, SMS count, created at. Top: search input (debounced 300ms, calls API), status filter dropdown (All / Trial / Active / Cancelled). Row click → `/admin/businesses/:id`. Pagination: previous/next with page number display. | §12      |
| P12-FE-029 | Create `src/pages/admin/AdminBusinessDetailPage.vue` — sections: (1) Business header (name, email, phone, subscription status badge, created at); (2) Intervention panel — "Prolonger l'essai" form (days input 1–90, submit), "Annuler l'abonnement" button (confirmation modal, reason textarea), "Impersonner" button (opens business dashboard in new tab using impersonation token); (3) Recent reservations table (last 10); (4) SMS log summary (total/delivered/failed/cost). | §12      |
| P12-FE-030 | Create `src/components/admin/ImpersonateModal.vue` — shows warning "Vous allez accéder au tableau de bord de {businessName} pendant 15 minutes", confirm button opens `/dashboard` in a new tab with `?impersonation_token=` query param. Frontend business app reads this param on mount: if present, use it as bearer token temporarily (store in `sessionStorage`, not `localStorage`, cleared after 15min or on tab close). | §12      |
| P12-FE-031 | Create `src/pages/admin/AdminAuditPage.vue` — paginated table (50/page). Columns: date/time, admin name, action (badge), target type, target ID (clickable → `/admin/businesses/:id` if target_type=Business), payload summary. Filters: action dropdown, date range pickers. | §10      |
| P12-FE-032 | Add "Documentation API" link in `AdminLayout.vue` nav — external link to `/api/docs` (L5-Swagger Swagger UI). Opens in new tab. Also add to `HelpIndexView.vue` as a card "API Swagger". | §11      |

#### 4.20.4 Front-end Tests

> **Lesson from Phase 11:** Any test that mounts a component using `<RouterView />` or `<RouterLink />` (directly or via a layout) MUST stub them. `AdminLayout.vue` uses `<RouterView />` — all page tests that render through `AdminLayout` must include `RouterView: true` and `RouterLink: { props: ['to'], template: '<a :href="to"><slot /></a>' }` in the `global.stubs` option. Also: never import `vi` from Vitest unless it is actually used in that test file — oxlint will fail the pre-push hook.

| Test File                                                              | Test Cases                                                                                                                                                   |
|------------------------------------------------------------------------|--------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `src/stores/__tests__/admin.test.ts`                                   | `login()` stores token, `logout()` clears state + localStorage, `isAuthenticated` true after login, `isAuthenticated` false after logout                    |
| `src/pages/admin/__tests__/AdminLoginPage.test.ts`                     | Form submits on enter, 401 shows error message, 429 shows lockout message, successful login redirects to `/admin/dashboard`                                  |
| `src/pages/admin/__tests__/AdminDashboardPage.test.ts`                 | Renders 6 stat cards, health panel shows all 4 indicators, green dot for queue healthy, red dot when failed jobs > 0; stub `RouterView` + `RouterLink`      |
| `src/pages/admin/__tests__/AdminBusinessListPage.test.ts`              | Renders table with business rows, search debounce calls API after 300ms, status filter emits correct API param, row click navigates to detail; stub `RouterView` + `RouterLink` |
| `src/components/admin/__tests__/StatCard.test.ts`                      | Renders label + value, correct color class applied                                                                                                           |

#### 4.20.5 DevOps / Infrastructure Tasks

| ID         | Task                                                                                                                      | PRD Ref |
|------------|---------------------------------------------------------------------------------------------------------------------------|---------|
| P12-DO-020 | Verify `make swagger` runs `php artisan l5-swagger:generate` successfully after all annotations added. Confirm `/api/docs` accessible in local dev. | §11     |
| P12-DO-021 | Add `make routes` output verification in local dev documentation (README or Makefile comment): `make routes` → `php artisan route:list --columns=method,uri,name,action,middleware`. | §11     |

#### 4.20.6 Deliverables Checklist

- [ ] `/admin/login` renders and authenticates with admin credentials
- [ ] Admin routes protected: unauthenticated `/admin/dashboard` redirects to `/admin/login`
- [ ] KPI stat cards show live data from `GET /api/v1/admin/stats`
- [ ] System health panel updates every 30 seconds
- [ ] Business list searchable, filterable, paginated
- [ ] Trial extension form works and shows updated `trial_ends_at`
- [ ] Subscription cancellation requires confirmation modal with reason
- [ ] Impersonation opens business dashboard in new tab with temporary token
- [ ] Audit log table displays all recorded actions
- [ ] `/api/docs` renders Swagger UI with all endpoints documented
- [ ] `make swagger` and `make routes` both run without errors
- [ ] All Sprint 20 Vitest tests passing
- [ ] CI pipeline green

---

## Section 5 — API Endpoints Delivered in Phase 12

| Method | Endpoint                                          | Controller                    | Auth            | Notes                                                                                                        |
|--------|---------------------------------------------------|-------------------------------|-----------------|--------------------------------------------------------------------------------------------------------------|
| POST   | `/api/v1/admin/login`                             | `AdminAuthController`         | No              | Body: `{email, password}`. Returns `{token, admin}`. Lockout after 5 failures (Redis, 15 min).              |
| POST   | `/api/v1/admin/logout`                            | `AdminAuthController`         | Bearer (admin)  | Revokes current admin token. Returns 204.                                                                    |
| GET    | `/api/v1/admin/stats`                             | `AdminDashboardController`    | Bearer (admin)  | Returns platform KPIs: business counts by status, SMS sent/cost this month, failed jobs.                     |
| GET    | `/api/v1/admin/system/health`                     | `AdminSystemController`       | Bearer (admin)  | Returns: queue heartbeat, failed jobs, Redis ping, last webhook at, DB status.                               |
| GET    | `/api/v1/admin/businesses`                        | `AdminBusinessController`     | Bearer (admin)  | Paginated list. Accepts `?search=`, `?status=`, `?sort=`, `?page=`. Returns `AdminBusinessResource` paginated. |
| GET    | `/api/v1/admin/businesses/{id}`                   | `AdminBusinessController`     | Bearer (admin)  | Full business detail: fields + last 10 reservations + SMS summary.                                           |
| PATCH  | `/api/v1/admin/businesses/{id}/extend-trial`      | `AdminBusinessController`     | Bearer (admin)  | Body: `{days: int}`. Extends `trial_ends_at`. Logs audit. Returns updated `AdminBusinessResource`.           |
| PATCH  | `/api/v1/admin/businesses/{id}/cancel-subscription` | `AdminBusinessController`   | Bearer (admin)  | Body: `{reason: string}`. Sets `subscription_status = cancelled`. Logs audit. Returns updated resource.       |
| POST   | `/api/v1/admin/businesses/{id}/impersonate`       | `AdminBusinessController`     | Bearer (admin)  | Issues 15-min business token with `impersonate` ability. Logs audit. Returns `{impersonation_token, expires_at}`. |
| GET    | `/api/v1/admin/audit-logs`                        | `AdminAuditController`        | Bearer (admin)  | Paginated list. Accepts `?admin_id=`, `?action=`, `?target_type=`. Returns `AdminAuditLogResource` paginated. |

*(Swagger UI at `/api/docs` serves all Phase 1–12 endpoints from L5-Swagger OpenAPI JSON.)*

---

## Section 6 — Exit Criteria

| # | Criterion                                                                                                         | Validated |
|---|-------------------------------------------------------------------------------------------------------------------|-----------|
| 1 | All functional requirements in Section 3 (Included rows) are implemented and manually verified                    | [ ]       |
| 2 | Backend test coverage ≥ 80% (Pest + PHPStan passing)                                                              | [ ]       |
| 3 | Frontend test coverage ≥ 70% (Vitest)                                                                             | [ ]       |
| 4 | Admin login lockout triggers after 5 failed attempts (verified manually)                                          | [ ]       |
| 5 | Business-scoped bearer token cannot access any `/admin/*` endpoint (returns 403)                                  | [ ]       |
| 6 | All 9 admin API endpoints return correct data and correct HTTP status codes                                        | [ ]       |
| 7 | `make swagger` runs without errors and produces valid OpenAPI JSON                                                 | [ ]       |
| 8 | `/api/docs` Swagger UI renders all Phase 1–12 endpoints with correct auth annotations                             | [ ]       |
| 9 | `make routes` displays all registered routes in terminal                                                           | [ ]       |
| 10| Admin audit log has one entry for each of: extend-trial, cancel-subscription, impersonate (verified in DB)        | [ ]       |
| 11| Impersonation token expires after 15 minutes (verify token TTL in `personal_access_tokens`)                       | [ ]       |
| 12| System health panel shows correct queue status (green when worker running, red when `znz:worker:heartbeat` absent) | [ ]       |
| 13| `pnpm lint`, `pnpm exec prettier --check .`, Pint, PHPStan all pass (note: `pnpm format:check` does not exist — use `pnpm exec prettier --check .`) | [ ]       |
| 14| CI pipeline green on `main` after merge                                                                            | [ ]       |

---

## Section 7 — Risks Specific to Phase 12

| Risk                                                                         | Probability | Impact | Mitigation                                                                                          |
|------------------------------------------------------------------------------|-------------|--------|-----------------------------------------------------------------------------------------------------|
| L5-Swagger incompatible with Laravel 12 (version pinning issue)              | Medium      | High   | Test `composer require darkaonline/l5-swagger` before starting P12-BE-001; pin known-good version   |
| Impersonation token leakage (stored in `localStorage` or URL)                | Low         | High   | Store in `sessionStorage` only (cleared on tab close); never put token in persistent storage; warn in UI |
| Admin brute-force (admin login not rate-limited initially)                   | Medium      | High   | P12-BE-003 implements Redis lockout (5 attempts, 15 min); apply Laravel `throttle:5,15` middleware too |
| Retroactive Swagger annotations break CI if annotations have syntax errors   | Medium      | Medium | Run `php artisan l5-swagger:generate` in CI as a lint step; fail-fast on annotation errors           |
| Queue heartbeat key missing on cold start (false "unhealthy" in health panel) | Medium      | Low    | Set 60-second TTL; UI shows "Unknown" (yellow) rather than "Error" (red) if key absent for < 2 min  |
| Swagger UI exposes internal endpoint details to unauthorized users           | Low         | Medium | Protect `/api/docs` with admin middleware in production (`APP_ENV=production`); allow open in local   |

---

## Section 8 — External Dependencies

| Service/Library              | Phase 12 Usage                                                        | Fallback if Unavailable                                              |
|------------------------------|-----------------------------------------------------------------------|----------------------------------------------------------------------|
| `darkaonline/l5-swagger`     | Generates OpenAPI JSON from PHP annotations; serves Swagger UI        | Hand-write `openapi.yaml`; use Stoplight or Swagger Editor externally |
| Redis                        | Admin lockout keys (`admin:lockout:{email}`), queue heartbeat key     | Graceful degradation: skip lockout if Redis unavailable; log warning |
| Stripe Dashboard             | Subscription billing mutations are performed manually in Stripe       | Admin panel shows current status; no programmatic Stripe mutations    |
| Twilio Webhook               | `last_twilio_webhook_at` derived from `sms_logs.created_at`          | Show "N/A" if no webhook data within 24h                             |

---

## Diagrams

### Admin Architecture Overview

```
┌─────────────────────────────────────────────────────────────────┐
│                        Docker Network                           │
│                                                                 │
│  ┌─────────┐   /api/v1/*      ┌──────────────────┐             │
│  │ Business│ ──────────────►  │  Laravel API     │             │
│  │ SPA     │                  │  (PHP-FPM)        │             │
│  │ /       │   /api/v1/admin/*│                  │             │
│  └─────────┘                  │  Routes:          │             │
│                               │  - Auth routes    │             │
│  ┌─────────┐ ──────────────►  │  - Business routes│◄──┐        │
│  │ Admin   │                  │  - Admin routes   │   │        │
│  │ SPA     │   /api/docs      │  - Public routes  │   │        │
│  │ /admin  │ ──────────────►  │                  │   │        │
│  └─────────┘                  │  Middleware:       │   │        │
│                               │  auth:sanctum     │   │        │
│                               │  admin.ability    │   │        │
│                               └────────┬─────────┘   │        │
│                                        │              │        │
│                          ┌─────────────▼──────────┐  │        │
│                          │     PostgreSQL          │  │        │
│                          │  businesses             │  │        │
│                          │  admins                 │  │        │
│                          │  admin_audit_logs       │  │        │
│                          │  reservations           │  │        │
│                          │  sms_logs               │  │        │
│                          └────────────────────────┘  │        │
│                                                        │        │
│                          ┌─────────────────────────┐  │        │
│                          │     Redis               ├──┘        │
│                          │  admin:lockout:*         │           │
│                          │  znz:worker:heartbeat    │           │
│                          └─────────────────────────┘           │
└─────────────────────────────────────────────────────────────────┘
```

### Admin Action Flow

```
flowchart TD
    A[Admin logs in POST /admin/login] --> B{Credentials valid?}
    B -- No, attempt < 5 --> C[Return 401]
    B -- No, attempt = 5 --> D[Redis lock 15min → Return 401]
    B -- Yes --> E[Issue Sanctum token ability=admin]
    E --> F[Admin accesses /admin/dashboard]
    F --> G[GET /admin/stats + /admin/system/health]
    G --> H[Admin selects a business]
    H --> I[GET /admin/businesses/id]
    I --> J{Action?}
    J -- Extend trial --> K[PATCH extend-trial + audit log]
    J -- Cancel sub --> L[PATCH cancel-subscription + audit log]
    J -- Impersonate --> M[POST impersonate → 15min token + audit log]
    M --> N[Open /dashboard?impersonation_token= in new tab]
    N --> O[Business SPA reads token from URL → sessionStorage]
    O --> P[Browse as business for 15min, then token expires]
```

---

## Assumptions

> The following assumptions were made during spec generation. Review and adjust before implementation begins.

- `darkaonline/l5-swagger` is compatible with Laravel 12. If not, consider `vyuldashev/laravel-openapi` as an alternative.
- Admin credentials are seeded via `CreateAdminSeeder` using env variables `ADMIN_EMAIL` and `ADMIN_PASSWORD`. There is no self-registration for admins.
- The business impersonation flow uses a URL query parameter (`?impersonation_token=`) which is immediately moved to `sessionStorage` and cleared from the URL via `history.replaceState`. The token is never stored in `localStorage`.
- Swagger UI served at `/api/docs` is open (no auth) in local/staging environments. In production, it should be protected behind admin middleware.
- `failed_jobs` Laravel table is used as-is (standard Laravel table, exists from Phase 1 queue setup).
- The existing `businesses` table has a one-to-one relationship with the business owner user (the `users` table from Sanctum) — the admin impersonation token is issued for the `users.id` linked to the business.
