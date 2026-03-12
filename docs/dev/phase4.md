# Phase 4 — Task Tracking

> **Status**: Not started
> **Spec**: [docs/phases/phase4.md](../phases/phase4.md)
> **Last audit**: 2026-03-12

---

## Sprint 4 — Hardening & Launch (Weeks 5–6)

### Backend

| ID         | Task                                                                          | Status | Owner |
|------------|-------------------------------------------------------------------------------|--------|-------|
| P4-BE-001  | Configure rate limiters (login, register, reservations, confirmation, webhook) | todo   | —     |
| P4-BE-002  | Harden Twilio HMAC validation (full URL reconstruction, Sentry on failure)    | todo   | —     |
| P4-BE-003  | Handle edge case: appointment < 2h → immediate SMS instead of verification    | todo   | —     |
| P4-BE-004  | Handle edge case: appointment < 30min → no SMS, return warning                | todo   | —     |
| P4-BE-005  | Handle duplicate confirmation (idempotent 200 responses per state)            | todo   | —     |
| P4-BE-006  | Install and configure Sentry (sentry/sentry-laravel, exception filtering)     | todo   | —     |
| P4-BE-007  | Create HealthController (DB + Redis + queue checks, < 100ms)                  | todo   | —     |
| P4-BE-008  | Create PurgeSmsLogs command (90-day, --dry-run, daily schedule)               | todo   | —     |
| P4-BE-009  | Configure global exception handler (consistent JSON error format)             | todo   | —     |
| P4-BE-010  | Write production .env configuration guide for Forge                           | todo   | —     |
| P4-BE-011  | Create RunSmokeTests command (programmatic critical path checks)              | todo   | —     |
| P4-BE-012  | Create forge-deploy.sh deploy script (down → pull → migrate → build → up)    | todo   | —     |

### Frontend

| ID         | Task                                                                          | Status | Owner |
|------------|-------------------------------------------------------------------------------|--------|-------|
| P4-FE-001  | Create useToast composable (success/error/warning, auto-dismiss, queue)       | todo   | —     |
| P4-FE-002  | Create ToastContainer.vue (stack, ARIA alerts, Escape key, transitions)       | todo   | —     |
| P4-FE-003  | Add loading + error states to all pages (spinner, ErrorMessage, retry button) | todo   | —     |
| P4-FE-004  | Create LoadingSpinner.vue (sm/md/lg, role=status, aria-label)                 | todo   | —     |
| P4-FE-005  | Accessibility audit + fixes (Lighthouse 100: labels, focus, contrast, h1)     | todo   | —     |
| P4-FE-006  | Vite production optimisation (manual chunks, Brotli, Lighthouse ≥ 90)        | todo   | —     |
| P4-FE-007  | Add 429 handling to Axios interceptor (toast + Retry-After backoff)           | todo   | —     |
| P4-FE-008  | Add 503/network error handling + @sentry/vue initialisation                   | todo   | —     |

### DevOps

| ID         | Task                                                                          | Status | Owner |
|------------|-------------------------------------------------------------------------------|--------|-------|
| P4-DO-001  | Provision DigitalOcean Droplet + Docker + SSH access + GitHub secrets        | todo   | —     |
| P4-DO-002  | Create docker-compose.prod.yml (api, worker, scheduler, nginx, postgres, redis) | todo | —   |
| P4-DO-003  | Create nginx/prod.conf (SSL, proxy, gzip, security headers)                  | todo   | —     |
| P4-DO-004  | Create .github/workflows/deploy.yml (GHCR build → SSH → rolling → rollback)  | todo   | —     |
| P4-DO-005  | Set up SSL with Certbot + Let's Encrypt + auto-renewal                       | todo   | —     |
| P4-DO-006  | Configure Sentry + test exception from production container                   | todo   | —     |
| P4-DO-007  | Configure UptimeRobot (health endpoint, 60s, SMS+email alerts)               | todo   | —     |
| P4-DO-008  | Configure pg_dump backup → DigitalOcean Spaces (7-day retention)             | todo   | —     |

---

## Audit Notes

| Date       | Note               |
|------------|--------------------|
| 2026-03-12 | Initial generation |
| 2026-03-12 | Added "Design System — Références Obligatoires" section avec règles toasts, pages d'erreur, emails Blade, logo emails |
