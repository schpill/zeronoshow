# Phase 12 — Task Tracking

> **Status**: Not started
> **Spec**: [docs/phases/phase12.md](../phases/phase12.md)
> **Last audit**: 2026-03-15

---

## Sprint 19 — Admin API: Auth, Business Management & Audit (Weeks 35–36)

### Backend

| ID         | Task                                                                            | Status | Owner |
|------------|---------------------------------------------------------------------------------|--------|-------|
| P12-BE-001 | Install `darkaonline/l5-swagger`, publish config, verify `l5-swagger:generate` | todo   | —     |
| P12-BE-002 | Create `Admin` model with `HasUuids`, `HasApiTokens`, Sanctum config            | todo   | —     |
| P12-BE-003 | `AdminAuthController::login()` with Redis lockout after 5 attempts              | todo   | —     |
| P12-BE-004 | `AdminAuthController::logout()` — revoke current token, return 204              | todo   | —     |
| P12-BE-005 | Create `EnsureAdminAbility` middleware, register as `admin.ability`             | todo   | —     |
| P12-BE-006 | Register `/v1/admin` route group with `auth:sanctum + admin.ability` middleware | todo   | —     |
| P12-BE-007 | `AdminDashboardController::stats()` — platform KPIs in single aggregate query   | todo   | —     |
| P12-BE-008 | `AdminBusinessController::index()` — paginated + search + status filter         | todo   | —     |
| P12-BE-009 | `AdminBusinessController::show()` — full detail + reservations + SMS summary    | todo   | —     |
| P12-BE-010 | `AdminBusinessController::extendTrial()` — validate days, extend, log audit     | todo   | —     |
| P12-BE-011 | `AdminBusinessController::cancelSubscription()` — reason, set status, log audit | todo   | —     |
| P12-BE-012 | `AdminBusinessController::impersonate()` — 15-min token, log audit              | todo   | —     |
| P12-BE-013 | `AdminAuditLog` model + `AdminAuditController::index()` with filters             | todo   | —     |
| P12-BE-014 | `AdminSystemController::health()` — queue, failed jobs, Redis, webhook, DB      | todo   | —     |
| P12-BE-015 | Swagger `@OA\Info` + `@OA\SecurityScheme` on base `Controller.php`              | todo   | —     |
| P12-BE-016 | Swagger annotations on `AuthController`, `ReservationController`, `DashboardController` | todo | — |
| P12-BE-017 | Swagger annotations on `WaitlistController`, `BookingWidgetController`, `CustomerController`, `ReputationController`, `LeoController`, `VoiceController`, `SubscriptionController` | todo | — |
| P12-BE-018 | Swagger annotations on all Phase 12 admin controllers                           | todo   | —     |
| P12-BE-019 | `CreateAdminSeeder` — seeds admin from `ADMIN_EMAIL` + `ADMIN_PASSWORD` env     | todo   | —     |

### DevOps

| ID         | Task                                                                             | Status | Owner |
|------------|----------------------------------------------------------------------------------|--------|-------|
| P12-DO-001 | Add `ADMIN_EMAIL`, `ADMIN_PASSWORD` to `.env` + `docker-compose.yml` env block  | todo   | —     |
| P12-DO-002 | Set `L5_SWAGGER_GENERATE_ALWAYS=true` in local `.env`, false in production      | todo   | —     |
| P12-DO-003 | Add `znz:worker:heartbeat` Redis key in scheduler (everyMinute, TTL 60s)        | todo   | —     |

---

## Sprint 20 — Admin Frontend & Swagger UI (Weeks 37–38)

### Frontend

| ID         | Task                                                                                     | Status | Owner |
|------------|------------------------------------------------------------------------------------------|--------|-------|
| P12-FE-020 | Add admin routes to `router/index.ts` with `requiresAdmin` meta guard                   | todo   | —     |
| P12-FE-021 | Create `src/stores/admin.ts` (Pinia) — separate from `useAuthStore`                     | todo   | —     |
| P12-FE-022 | Create `src/api/adminAxios.ts` — separate Axios instance for admin API                  | todo   | —     |
| P12-FE-023 | Create `src/layouts/AdminLayout.vue` — nav + sidebar + logout                           | todo   | —     |
| P12-FE-024 | Create `src/pages/admin/AdminLoginPage.vue`                                              | todo   | —     |
| P12-FE-025 | Create `src/pages/admin/AdminDashboardPage.vue` — KPIs + health panel (polls 30s)       | todo   | —     |
| P12-FE-026 | Create `src/components/admin/StatCard.vue`                                               | todo   | —     |
| P12-FE-027 | Create `src/components/admin/HealthIndicator.vue`                                        | todo   | —     |
| P12-FE-028 | Create `src/pages/admin/AdminBusinessListPage.vue` — search, filter, paginate           | todo   | —     |
| P12-FE-029 | Create `src/pages/admin/AdminBusinessDetailPage.vue` — interventions + history          | todo   | —     |
| P12-FE-030 | Create `src/components/admin/ImpersonateModal.vue` — sessionStorage token, new tab      | todo   | —     |
| P12-FE-031 | Create `src/pages/admin/AdminAuditPage.vue` — paginated audit log table                 | todo   | —     |
| P12-FE-032 | Add "Documentation API" link in `AdminLayout.vue` + `HelpIndexView.vue`                 | todo   | —     |

### DevOps

| ID         | Task                                                                             | Status | Owner |
|------------|----------------------------------------------------------------------------------|--------|-------|
| P12-DO-020 | Verify `make swagger` produces valid OpenAPI JSON, `/api/docs` accessible        | todo   | —     |
| P12-DO-021 | Document `make routes` in Makefile comment                                       | todo   | —     |

---

## Audit Notes

| Date       | Note                  |
|------------|-----------------------|
| 2026-03-15 | Initial generation    |
| 2026-03-15 | Lessons from phase 11 applied to spec: (1) `pnpm format:check` corrigé en `pnpm exec prettier --check .` dans le critère #13 ; (2) stub `RouterView`/`RouterLink` ajouté dans la section tests frontend ; (3) ne pas importer `vi` de Vitest sans l'utiliser (oxlint bloque le push). |
