# Phase 1 — Task Tracking

> **Status**: Not started
> **Spec**: [docs/phases/phase1.md](../phases/phase1.md)
> **Last audit**: 2026-03-12

---

## Sprint 1 — Foundation (Weeks 1–2)

### Backend

| ID         | Task                                                                  | Status | Owner |
|------------|-----------------------------------------------------------------------|--------|-------|
| P1-BE-001  | Create Laravel 12 project, configure composer.json and .env.example   | todo   | —     |
| P1-BE-002  | Install and configure Laravel Sanctum (30-day token expiry)           | todo   | —     |
| P1-BE-003  | Install Twilio SDK, add env vars to .env.example                      | todo   | —     |
| P1-BE-004  | Configure PostgreSQL + Redis connections in config/database.php       | todo   | —     |
| P1-BE-005  | Create RequireActiveSubscription middleware (trial/active check → 402) | todo  | —     |
| P1-BE-006  | Write all 4 DB migrations (businesses, customers, reservations, sms_logs) | todo | —  |
| P1-BE-007  | Create Business model (HasApiTokens, isOnActivePlan accessor, relationships) | todo | — |
| P1-BE-008  | Create Customer model (getScoreTier method, relationships)            | todo   | —     |
| P1-BE-009  | Create Reservation model (casts, relationships, scopeNeedingReminder) | todo   | —     |
| P1-BE-010  | Create SmsLog model (casts, relationships)                            | todo   | —     |
| P1-BE-011  | Create SmsServiceInterface contract (send, validateWebhookSignature)  | todo   | —     |
| P1-BE-012  | Create TwilioSmsService implementing SmsServiceInterface              | todo   | —     |
| P1-BE-013  | Bind SmsServiceInterface in AppServiceProvider + config/services.php  | todo   | —     |
| P1-BE-014  | Create RegisterRequest form request (E.164 phone, unique email, etc.) | todo   | —     |
| P1-BE-015  | Create AuthController (register, login, logout)                       | todo   | —     |
| P1-BE-016  | Create StoreReservationRequest (E.164, future date, optional fields)  | todo   | —     |
| P1-BE-017  | Create ReservationController::store (find/create customer, dispatch SMS job) | todo | — |
| P1-BE-018  | Create ReservationController::index (date/week filter, Redis cache)   | todo   | —     |
| P1-BE-019  | Create ReservationController::show (policy check, includes sms_logs)  | todo   | —     |
| P1-BE-020  | Create ReservationResource (all fields, nested customer)              | todo   | —     |
| P1-BE-021  | Create SendVerificationSms job (3 retries, backoff, SmsLog creation)  | todo   | —     |
| P1-BE-022  | Create ConfirmationController::show (token validation, Blade view)    | todo   | —     |
| P1-BE-023  | Create ConfirmationController::confirm (status update, token invalidation) | todo | — |
| P1-BE-024  | Create stub RecalculateReliabilityScore job                           | todo   | —     |
| P1-BE-025  | Create stub TwilioWebhookController (log + 200)                       | todo   | —     |
| P1-BE-026  | Create CustomerController::lookup (phone → score/tier)                | todo   | —     |
| P1-BE-027  | Configure routes/api.php (versioned /api/v1) + routes/web.php         | todo   | —     |
| P1-BE-028  | Create Blade views for confirmation flow (show + result pages)        | todo   | —     |

### Frontend

| ID         | Task                                                                  | Status | Owner |
|------------|-----------------------------------------------------------------------|--------|-------|
| P1-FE-001  | Scaffold Vue.js 3 SPA in frontend/ with pnpm (Vue Router, Pinia, ESLint, Prettier) | todo | — |
| P1-FE-002  | Tailwind CSS 3: darkMode class, Inter+JetBrains Mono fonts, brand palette, content paths | todo | — |
| P1-FE-003  | Create api/axios.js (baseURL, auth interceptor, 401/402 redirects)    | todo   | —     |
| P1-FE-004  | Create Pinia auth store (login, register, logout, isAuthenticated)    | todo   | —     |
| P1-FE-005  | Create Vue Router with auth navigation guard                          | todo   | —     |
| P1-FE-006  | Create AppLayout.vue with trial expiry warning banner                 | todo   | —     |
| P1-FE-007  | Create NavBar.vue (business name, New Reservation, Logout, responsive) | todo  | —     |
| P1-FE-008  | Create LoginPage.vue (form, errors, redirect)                         | todo   | —     |
| P1-FE-009  | Create RegisterPage.vue (all fields, errors, redirect)                | todo   | —     |
| P1-FE-010  | Create useReservations composable (CRUD + lookup actions)             | todo   | —     |
| P1-FE-011  | Create ReservationForm.vue (all fields, phone lookup, badge, submit)  | todo   | —     |
| P1-FE-012  | Create ReliabilityBadge.vue (4 tiers, color pills, aria-label)        | todo   | —     |
| P1-FE-013  | Create stub Dashboard.vue wrapping ReservationForm                    | todo   | —     |
| P1-FE-014  | CSS utility layer (@layer components: .text-heading-* etc.) + Google Fonts in index.html | todo | — |

### DevOps

| ID         | Task                                                                  | Status | Owner |
|------------|-----------------------------------------------------------------------|--------|-------|
| P1-DO-001  | Monorepo structure (backend/ + frontend/) + docker-compose.yml local dev | todo | —   |
| P1-DO-002  | Create backend/docker/php/Dockerfile (PHP 8.3-fpm, PCOV, Redis ext)  | todo   | —     |
| P1-DO-003  | Create backend/.env.example with all vars                             | todo   | —     |
| P1-DO-004  | Create .github/workflows/ci.yml (Pest + Pint + PHPStan + pnpm Vitest) | todo  | —     |

---

## Audit Notes

| Date       | Note                    |
|------------|-------------------------|
| 2026-03-12 | Initial generation      |
| 2026-03-12 | Updated P1-FE-001/002 (pnpm, design system config); added P1-FE-014 (CSS utility layer + fonts) |
| 2026-03-12 | Added "Design System — Références Obligatoires" section (colors.md, polices.md, 3 logos SVG, 3 templates HTML) comme référence impérative avant Section 4 |
