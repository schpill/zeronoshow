# Phase 4 — Hardening & Launch

| Field            | Value                                                                                               |
|------------------|-----------------------------------------------------------------------------------------------------|
| **Phase**        | 4 of 4                                                                                              |
| **Name**         | Hardening & Launch                                                                                  |
| **Duration**     | Weeks 5–6 (2 weeks)                                                                                 |
| **Milestone**    | M4 — 3 external businesses use ZeroNoShow in real conditions for 1 week without critical issues     |
| **PRD Sections** | §10 (Security), §12 (Success Criteria), §13 Phase 4, §14 (Risks), §8 (Infrastructure)             |
| **Prerequisite** | Phase 3 fully completed and validated (M3 milestone achieved, all exit criteria met)                |
| **Status**       | Not started                                                                                         |

---

## Section 1 — Phase Objectives

| ID       | Objective                                                                                                        | Verifiable?                   |
|----------|------------------------------------------------------------------------------------------------------------------|-------------------------------|
| P4-OBJ-1 | Twilio webhook HMAC validation rejects every tampered request with 403                                           | Security test passes          |
| P4-OBJ-2 | Rate limiting correctly throttles login (10/15min/IP) and reservation creation (60/min/business)                | Feature test passes           |
| P4-OBJ-3 | All edge cases handled: appointment < 2h away, < 30min away, expired link, duplicate confirmation               | Feature test passes           |
| P4-OBJ-4 | Failed SMS jobs after 3 retries are logged in Sentry and visible in Horizon failed jobs                         | Manual verification           |
| P4-OBJ-5 | `sms-logs:purge` command deletes records older than 90 days                                                     | Feature test passes           |
| P4-OBJ-6 | Production server accessible at `zeronoshow.fr` with valid TLS certificate                                      | Manual verification           |
| P4-OBJ-7 | Health check endpoint responds 200 with system status in < 100ms                                                | Smoke test passes             |
| P4-OBJ-8 | 3 pilot businesses onboarded, each sending at least 5 real reservations with SMS delivered                      | Manual verification           |
| P4-OBJ-9 | Vue SPA Lighthouse score ≥ 90 (performance), 100 (accessibility) on production build                           | Lighthouse CI check           |
| P4-OBJ-10| End-to-end smoke test suite passes on production environment                                                    | CI smoke test job green       |

---

## Section 2 — Entry Criteria

- Phase 3 exit criteria all checked (Stripe billing, dashboard, trial enforcement all validated)
- Production server provisioned on DigitalOcean (droplet + managed PG + managed Redis)
- Laravel Forge account set up and connected to server
- Domain `zeronoshow.fr` pointing to production server
- Sentry project created with DSN
- UptimeRobot account created
- Stripe live mode keys available (STRIPE_KEY_LIVE, STRIPE_SECRET_LIVE) — can deploy with test keys for pilot if live not ready
- 3 pilot businesses identified and agreed to test

---

## Section 3 — Scope — Requirement Traceability

| PRD Requirement Group                          | IDs in PRD              | Status   | Notes                                                            |
|------------------------------------------------|-------------------------|----------|------------------------------------------------------------------|
| Rate limiting (login, register, reservations)  | §9 Security, §14 Risks  | Included |                                                                  |
| Twilio HMAC validation (full)                  | §10 Security, US-08     | Included | Replaced stub from Phase 1                                       |
| Edge cases: < 2h, < 30min, expired, duplicate  | Feature 1, Feature 4    | Included |                                                                  |
| Queue failure alerting                         | US-08, §12              | Included | Sentry + Horizon                                                 |
| SMS log 90-day purge                           | §10 Data Handling       | Included |                                                                  |
| Health check endpoint                          | §8 Observability        | Included |                                                                  |
| Production deployment                          | §13 Phase 4             | Included | DigitalOcean + Forge                                             |
| Sentry error tracking                          | §8 Observability        | Included |                                                                  |
| UptimeRobot monitoring                         | §8 Observability        | Included |                                                                  |
| Smoke tests for critical paths                 | §13 Phase 4, §12        | Included |                                                                  |
| Accessibility (Lighthouse 100)                 | §12 UX Goals            | Included |                                                                  |
| WhatsApp / Telegram                            | §4 Out of Scope         | No       | Deferred to V2                                                   |
| Third-party integrations                       | §4 Out of Scope         | No       | Deferred to V2                                                   |

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
| **Template backoffice client** | `docs/graphics/templates/template_backoffice_client.html` | Layout complet : sidebar w-60, header h-16, stats bar 4 colonnes, tableau réservations, formulaire rapide, **dark mode complet** | Charte visuelle de référence pour tous les correctifs et composants de ce sprint |
| **Template backoffice admin** | `docs/graphics/templates/template_backoffice_zeronoshow.html` | Espace admin ZNS : sidebar slate-900, accent violet, tableau établissements, health panel, graphique SMS, logs d'activité | Référence pour interface interne ZeroNoShow |
| **Template site vitrine** | `docs/graphics/templates/template_site_vitrine.html` | Landing page : hero, features, pricing, CTA — palette Emerald sur blanc | Pages publiques, page confirmation client `/c/{token}` |

### Règles impératives

1. **Aucune régression visuelle** — toute modification frontend doit être testée visuellement contre les templates HTML de référence. Un composant qui s'écarte visuellement des templates est un bug.
2. **Dark mode systématique** — livré en Phase 3, il doit être maintenu sur tous les correctifs Phase 4. Tout nouveau composant inclut ses variantes `dark:` dès le premier commit.
3. **Toasts et notifications** — couleurs sémantiques de `colors.md` : succès=emerald, erreur=red, avertissement=amber, info=blue. Messages en `.text-body-sm` (polices.md).
4. **Pages d'erreur (403/404/500)** — titres en `.text-heading-3`, descriptions en `.text-body` (polices.md). Fond `bg-slate-50 dark:bg-slate-950`, bouton retour en `bg-emerald-600` (colors.md).
5. **Emails Blade** — utiliser les couleurs sémantiques de `colors.md` pour les CTA (emerald pour actions positives, red pour alertes). Polices système safe (Arial/Helvetica) en fallback, Inter comme première intention.
6. **Logo dans les emails** — version PNG exportée depuis `zeronoshow-light.svg` (fond blanc) ou `zeronoshow-dark.svg` selon le fond de l'email.

---

## Section 4 — Sprint Breakdown

### 4.4 Sprint 4 — Hardening & Launch (Weeks 5–6)

#### 4.4.1 Sprint Objectives

- All security hardening tasks complete (rate limiting, HMAC, input sanitisation)
- All edge case flows handled with correct status codes and user-facing messages
- Sentry integrated and capturing exceptions in staging
- Production infrastructure provisioned and accessible
- Forge deployment pipeline deploying from `main` branch
- UptimeRobot alerting configured for `/api/v1/health`
- Smoke test suite passes on production URL
- 3 pilot businesses onboarded with real data

---

#### 4.4.2 Database Migrations

No schema changes in Phase 4.

---

#### 4.4.3 Back-end Tasks

| ID         | Task                                                                                                                                                                                                                                                                                                                                                                                            | PRD Ref              |
|------------|-------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|----------------------|
| P4-BE-001  | Configure Laravel rate limiting in `bootstrap/app.php` (or `RouteServiceProvider`). Define named limiters: `login`: 10 requests per 15 minutes per IP; `register`: 5 requests per hour per IP; `reservations`: 60 requests per minute per authenticated business ID; `confirmation`: 10 requests per token UUID; `webhook`: 200 requests per minute per IP. Apply via `->middleware('throttle:login')` etc. on respective routes. Return `429` with `Retry-After` header. | §9 Security          |
| P4-BE-002  | Update `TwilioWebhookController::handle` to move HMAC validation before any payload parsing. Use `Twilio\Security\RequestValidator` from `twilio/sdk`. Reconstruct full webhook URL from `config('app.url')` + request path. Fail fast with 403 + log warning to Sentry if signature mismatch. Add integration test covering tampered payload (verified in Phase 2 tests, hardened here). | §10 Security         |
| P4-BE-003  | Handle edge case in `ReservationController::store`: if `scheduled_at` is < 2h from now AND ≥ 30min: skip verification SMS, generate token, set status `pending_verification`, send immediate reminder-style SMS `"Votre RDV est dans moins de 2h chez {business}. Confirmez: {url}"`. Dispatch as `SendVerificationSms` with custom body parameter.                                          | Feature 1, US-02     |
| P4-BE-004  | Handle edge case in `ReservationController::store`: if `scheduled_at` is < 30min from now: set status `pending_verification`, set `confirmation_token = null`, `token_expires_at = null`. Do NOT send any SMS. Return reservation with `warning: "Appointment too soon for SMS confirmation"` in response. Add this to `StoreReservationRequest` validation message as a warning (not a hard error — business may still want to record it). | Feature 1            |
| P4-BE-005  | Handle duplicate confirmation edge case in `ConfirmationController::confirm`: if reservation status is already `confirmed` and action is `confirm` → return 200 "Vous avez déjà confirmé ce rendez-vous" (idempotent). If already `cancelled_by_client` and action is `cancel` → return 200 "Vous avez déjà annulé ce rendez-vous". If status is terminal (`no_show`, `show`, `cancelled_no_confirmation`) → return 410 with appropriate message.  | Feature 4, US-05     |
| P4-BE-006  | Install and configure Sentry: `composer require sentry/sentry-laravel`. Publish config. Set `SENTRY_LARAVEL_DSN` in `.env.example`. Configure `config/sentry.php`: `traces_sample_rate = 0.1` (10% tracing for MVP). Integrate in `bootstrap/app.php` exception handler: report exceptions to Sentry. Exclude `AuthenticationException`, `ValidationException`, `ThrottleRequestsException` from Sentry (expected exceptions). | §8 Observability     |
| P4-BE-007  | Create `app/Http/Controllers/HealthController.php`. Method `check(): JsonResponse`. Checks: (1) DB connection (`DB::connection()->getPdo()` — catch exception). (2) Redis ping (`Redis::ping()`). (3) Queue worker running (check Horizon workers count > 0 via Horizon API or Redis key). Returns `200 {status: 'ok', db: 'ok', redis: 'ok', queue: 'ok', version: config('app.version')}` if all pass. Returns `503 {status: 'degraded', ...}` if any fail. Route: `GET /api/v1/health` (no auth). Response time target: < 100ms.  | §8 Observability     |
| P4-BE-008  | Create `app/Console/Commands/PurgeSmsLogs.php`. Command `sms-logs:purge`. Logic: delete `sms_logs` WHERE `created_at < now() - 90 days`. Log count of deleted rows. Schedule in Kernel: `daily()` at `03:30`. Add `--dry-run` flag that logs count without deleting.                                                                                                                         | §10 Data Handling    |
| P4-BE-009  | Configure global exception handler in `bootstrap/app.php`. Map exception types to consistent JSON error responses: `ModelNotFoundException → 404 NOT_FOUND`, `AuthorizationException → 403 FORBIDDEN`, `ValidationException → 422 VALIDATION_ERROR` (already handled by Laravel), `ThrottleRequestsException → 429 RATE_LIMITED` with Retry-After. All unhandled exceptions → 500 INTERNAL_SERVER_ERROR with generic message (no stack trace in production). | §9 Security          |
| P4-BE-010  | Create production `.env` configuration guide (internal doc, not committed). Document: every env var, its source (Twilio dashboard / Stripe dashboard / generated), and format. Store securely in Forge environment variables panel.                                                                                                                                                           | §8 Infra             |
| P4-BE-011  | Write smoke test command `app/Console/Commands/RunSmokeTests.php`. Command `smoke:test`. Performs programmatic checks against the running app (not external HTTP): (1) Create test business in DB. (2) Create reservation → assert SMS job dispatched. (3) Simulate confirmation → assert status = confirmed. (4) Assert health endpoint returns 200. (5) Clean up test data. Return exit code 0 on pass, 1 on failure. Run as part of CI deploy job.  | §12 Success Criteria |
| P4-BE-012  | Add `forge-deploy.sh` deploy script. Steps: `php artisan down --secret=DEPLOY_SECRET` → `git pull` → `composer install --no-dev --optimize-autoloader` → `php artisan migrate --force` → `npm ci && npm run build` → `php artisan config:cache && php artisan route:cache && php artisan view:cache` → `php artisan queue:restart` → `php artisan up`. Triggered via Forge webhook on `main` push (after CI passes). | §8 Infra             |

---

#### 4.4.4 Back-end Tests (TDD)

| Test File                                                       | Test Cases                                                                                                                                                                                                                          |
|-----------------------------------------------------------------|-------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `tests/Feature/Security/RateLimitingTest.php`                  | login blocked after 10 attempts in 15 minutes; returns 429 with Retry-After header; register blocked after 5 attempts per hour; reservation creation blocked at 61st request per minute; confirmation blocked at 11th request per token |
| `tests/Feature/Reservation/EdgeCasesTest.php`                  | appointment < 2h sends immediate reminder-style SMS instead of verification; appointment < 30min does not send SMS, returns warning in response; appointment in past returns 422; guests=0 returns 422; guests=101 returns 422        |
| `tests/Feature/Confirmation/EdgeCasesTest.php`                 | confirm already-confirmed returns 200 idempotent message; cancel already-cancelled returns 200; confirm on no_show status returns 410; cancel on show status returns 410; duplicate POST within 30min window applies last action      |
| `tests/Feature/Health/HealthCheckTest.php`                     | returns 200 with status ok when DB and Redis available; returns 503 when DB unreachable (mocked); response time under 200ms; no auth required                                                                                        |
| `tests/Feature/Commands/PurgeSmsLogsTest.php`                  | deletes sms_logs older than 90 days; preserves logs newer than 90 days; dry-run flag logs count without deleting; logs deletion count                                                                                               |
| `tests/Feature/Security/WebhookSecurityTest.php`               | Twilio webhook with valid HMAC accepted; Twilio webhook with tampered payload rejected 403; Stripe webhook with valid signature accepted; Stripe webhook with invalid signature returns 400; HMAC validation uses full URL reconstruction |

---

#### 4.4.5 Front-end Tasks

> **Référence design obligatoire** — Toutes les tâches frontend doivent strictement respecter :
> - **Couleurs** : `docs/graphics/colors.md` — toasts : succès=emerald, erreur=red, avertissement=amber, info=blue (couleurs sémantiques §4 du fichier)
> - **Typographie** : `docs/graphics/polices.md` — messages de toast et d'erreur en `.text-body-sm`, titres des pages d'erreur en `.text-heading-3`
> - **Template de référence** : `docs/graphics/templates/template_backoffice_client.html` — charte visuelle globale à maintenir sur toutes les pages (espacement, arrondi `rounded-xl`, ombres, bordures)
> - **Dark mode** : livré en Phase 3 (P3-FE-011) — tous les nouveaux composants de Phase 4 doivent inclure leurs variantes `dark:` dès le premier commit

| ID         | Task                                                                                                                                                                                                                                                                                   | PRD Ref              |
|------------|----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|----------------------|
| P4-FE-001  | Implement toast notification system in `AppLayout.vue`. Create `resources/js/composables/useToast.js`: state `toasts[]`, methods `success(msg)`, `error(msg)`, `warning(msg)`. Auto-dismiss after 4 seconds. Queue multiple toasts. Stack renders `<ToastContainer>` in AppLayout. Replace all inline "alert" patterns from Phases 1–3 with toast notifications.  | §12 UX Goals         |
| P4-FE-002  | Create `resources/js/components/ToastContainer.vue`. Renders stack of `<Toast>` components. Each Toast: icon (check/x/warning), message, close button, progress bar (4s dismiss). ARIA: `role="alert"`, `aria-live="polite"`. Keyboard: close on Escape. Transitions: slide-in from right. | §12 UX Goals         |
| P4-FE-003  | Add loading and error states to all pages. Every API call: show spinner (`<LoadingSpinner>` component) while loading, show `<ErrorMessage>` component on error (message + "Réessayer" button). Ensure no page ever shows a blank white screen on API failure.                          | §12 UX Goals         |
| P4-FE-004  | Create `resources/js/components/LoadingSpinner.vue`. Props: `size: 'sm'\|'md'\|'lg'`, `label: string` (aria-label). Renders SVG spinner animation with Tailwind `animate-spin`. `role="status"`, `aria-label` set from prop. Used across all async operations.                         | §12 Accessibility    |
| P4-FE-005  | Conduct Lighthouse accessibility audit on all pages. Fix all issues to reach score = 100: ensure all form inputs have associated labels; all interactive elements have focus-visible styles; color contrast ≥ 4.5:1 for normal text; images have alt text; page has `<h1>` on every route. | §12 UX Goals         |
| P4-FE-006  | Configure Vite production build optimisation: `rollupOptions.output.manualChunks` to split vendor (vue, vue-router, pinia, axios) from app code. Enable Brotli compression plugin (`vite-plugin-compression`). Target Lighthouse Performance ≥ 90 on production build.               | §12 Success Criteria |
| P4-FE-007  | Add 429 rate limit handling to Axios response interceptor: show toast `"Trop de requêtes. Veuillez patienter X secondes."` (parse `Retry-After` header). Backoff retry for idempotent GET requests only (max 1 retry after Retry-After seconds).                                        | §9 Security          |
| P4-FE-008  | Add 503 / network error handling to Axios response interceptor: show persistent toast `"Service temporairement indisponible. Vos données sont sauvegardées."`. Log error to Sentry JS SDK (`@sentry/vue`). Add `@sentry/vue` to `resources/js/app.js` initialisation.                  | §8 Observability     |

---

#### 4.4.6 Front-end Tests

| Test File                                                     | Test Cases                                                                                                                                                                            |
|---------------------------------------------------------------|---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `tests/js/component/ToastContainer.spec.js`                  | renders success toast with correct icon and color; error toast correct; auto-dismisses after 4s (fake timer); close button removes toast; multiple toasts stack; Escape key closes top toast |
| `tests/js/unit/composables/useToast.spec.js`                 | success adds toast with type success; error adds toast with type error; toast auto-removed after TTL; warning adds correct type                                                        |
| `tests/js/component/LoadingSpinner.spec.js`                  | renders with role=status; aria-label matches prop; sm/md/lg size classes applied                                                                                                      |

---

#### 4.4.7 DevOps / Infrastructure Tasks

| ID         | Task                                                                                                                                                                                                                                                                                       | PRD Ref         |
|------------|--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|-----------------|
| P4-DO-001  | Provision server: DigitalOcean Droplet (2 vCPU, 4GB RAM, Ubuntu 24.04). Install Docker + Docker Compose v2. Create deploy user `znz` with SSH key (stored in GitHub secret `PROD_SSH_KEY`). Open ports 80/443/22 via UFW. Add GitHub secrets: `PROD_HOST`, `PROD_USER`, `PROD_SSH_KEY`, `PROD_APP_PATH`. No Forge — same SSH-direct pattern as Koomky. | §8 Infra, ADR-008 |
| P4-DO-002  | Create `docker-compose.prod.yml` at repo root. Services: `nginx` (nginx:1.27-alpine, ports 80/443, Let's Encrypt volumes), `api` (GHCR image `znz-api`, `APP_ENV=production`, `LOG_CHANNEL=stderr`, health check), `queue-worker` (same image, `php artisan queue:work --tries=3 --sleep=1 --max-time=3600`), `scheduler` (same image, `php artisan schedule:work`), `postgres` (postgres:16-alpine, named volume, health check), `redis` (redis:7-alpine, `--requirepass ${REDIS_PASSWORD}`, named volume). All `restart: unless-stopped`. Resource limits set per service. | §8 Infra        |
| P4-DO-003  | Create `backend/docker/nginx/prod.conf`. Nginx: listen 80 (redirect to 443) + 443 SSL, SSL certs from Let's Encrypt volume, proxy_pass PHP-FPM to `api:9000`, serve Vue static assets from `/build/`, gzip enabled, security headers: HSTS, X-Frame-Options, X-Content-Type-Options, Referrer-Policy. Test with `securityheaders.com` → A rating. | §9 Security     |
| P4-DO-004  | Create `.github/workflows/deploy.yml`. Mirrors Koomky pattern: (1) Build + push `znz-api` image to GHCR (`ghcr.io/{owner}/znz-api:{sha}`). (2) Build + push `znz-frontend` image. (3) SSH deploy: `docker compose pull` → `--scale api=2` rolling start → `php artisan migrate --force` → `--scale api=1`. (4) Health check `curl -fsS https://zeronoshow.fr/api/v1/health` → auto-rollback to previous image tags if fails. | §8 Infra        |
| P4-DO-005  | Set up SSL: install Certbot on server, obtain Let's Encrypt cert for `zeronoshow.fr`. Configure auto-renewal cron. Mount cert volume into nginx service in `docker-compose.prod.yml`. | §9 Security     |
| P4-DO-006  | Configure Sentry: create project, add `SENTRY_LARAVEL_DSN` + Sentry Vue DSN to `.env.prod` on server. Test: `docker compose exec api php artisan tinker` → `throw new \Exception('Sentry test')` → verify in Sentry within 30s. | §8 Observability|
| P4-DO-007  | Configure UptimeRobot: HTTP monitor on `https://zeronoshow.fr/api/v1/health` every 60 seconds, alert after 3 failures, SMS + email contacts. Share public status page URL with pilot businesses. | §8 Observability|
| P4-DO-008  | Configure PostgreSQL backups: daily `pg_dump` script on server, compress + store in DigitalOcean Spaces (S3-compatible, `s3cmd` or `rclone`), 7-day retention. Test restoration on staging. Document runbook. | §8 Infra        |

---

#### 4.4.8 Sprint Deliverables Checklist

- [ ] `GET /api/v1/health` returns 200 `{status: 'ok'}` in < 100ms on production
- [ ] Login rate limiting fires 429 after 10 attempts in 15 minutes
- [ ] Twilio webhook HMAC rejects tampered request (verified with test request)
- [ ] Appointment < 2h sends immediate confirmation SMS instead of standard verification
- [ ] Appointment < 30min records reservation without SMS, returns warning
- [ ] Duplicate confirmation returns 200 idempotent response (not 410)
- [ ] Sentry receives test exception from production within 30 seconds
- [ ] UptimeRobot alert fires when health endpoint is manually downed
- [ ] `sms-logs:purge` deletes records > 90 days (dry-run tested first)
- [ ] Production deploy script runs without errors via Forge
- [ ] Lighthouse scores: Performance ≥ 90, Accessibility = 100 on `/` and `/login`
- [ ] All 6 Phase 4 back-end test files pass
- [ ] All 3 Phase 4 front-end test files pass
- [ ] CI pipeline with smoke test job green on main
- [ ] 3 pilot businesses have created at least 5 reservations each with SMS delivered
- [ ] Security headers verified via securityheaders.com (A or A+ rating)

---

## Section 5 — API Endpoints Delivered in Phase 4

| Method | Endpoint              | Controller                | Auth | Notes                                                                                       |
|--------|-----------------------|---------------------------|------|---------------------------------------------------------------------------------------------|
| GET    | /api/v1/health        | HealthController::check   | No   | Returns {status, db, redis, queue, version}. 200 if all ok, 503 if degraded. < 100ms.     |

---

## Section 6 — Exit Criteria

| # | Criterion                                                                                                                  | Validated |
|---|----------------------------------------------------------------------------------------------------------------------------|-----------|
| 1 | All 6 Phase 4 back-end test files pass                                                                                     | [ ]       |
| 2 | All 3 Phase 4 front-end test files pass                                                                                    | [ ]       |
| 3 | Full test suite (all 4 phases) passes: `php artisan test` returns 0 failures                                               | [ ]       |
| 4 | Production server accessible at `https://zeronoshow.fr` with valid TLS (A rating on ssllabs.com)                          | [ ]       |
| 5 | Health endpoint responds in < 100ms on production                                                                          | [ ]       |
| 6 | Lighthouse Performance ≥ 90 and Accessibility = 100 on production                                                         | [ ]       |
| 7 | Security headers score A or A+ on securityheaders.com                                                                     | [ ]       |
| 8 | Sentry receiving exceptions from production environment                                                                    | [ ]       |
| 9 | UptimeRobot monitoring active with correct alert contacts                                                                  | [ ]       |
| 10 | 3 pilot businesses onboarded, each with ≥ 5 reservations and ≥ 1 confirmed SMS                                            | [ ]       |
| 11 | No critical bugs reported during 1-week pilot period                                                                       | [ ]       |
| 12 | All `sms_logs` for delivered messages have `cost_eur` populated (Twilio webhook working in production)                    | [ ]       |
| 13 | Daily backup confirmed via DigitalOcean Managed DB console                                                                 | [ ]       |

---

## Section 7 — Risks Specific to Phase 4

| Risk                                                                | Probability | Impact | Mitigation                                                                                          |
|---------------------------------------------------------------------|-------------|--------|-----------------------------------------------------------------------------------------------------|
| Production SMS delivery failure during pilot week                   | Low         | High   | Monitor Twilio delivery rates daily; have Vonage account ready as fallback                          |
| Forge deployment fails mid-deploy (leaves app in broken state)      | Low         | High   | `php artisan down --secret=` before deploy; `php artisan up` only in final step of deploy script    |
| Pilot business reports SMS not received (carrier filtering)         | Medium      | High   | Test with all 3 French carriers pre-launch; register Twilio sender ID for higher deliverability     |
| Sentry quota exceeded (free tier: 5k errors/month)                  | Low         | Low    | Exclude 4xx errors from Sentry; filter `ThrottleRequestsException` and `ValidationException`        |
| Rate limiting too aggressive, blocking legitimate business usage    | Low         | Medium | Set reservation limit to 60/min (generous for any real business); monitor 429 rate in Sentry        |
| DigitalOcean Managed DB connection pool exhausted under load        | Very Low    | High   | Configure `DB_POOL_MAX=10` in Laravel; droplet + managed DB on same datacenter (low latency)       |
| Pilot business accidentally marks all clients as No-Show            | Low         | Low    | 30-minute undo window handles accidental marks; add confirmation modal in Phase 4 FE (P4-FE-003 error states) |

---

## Section 8 — External Dependencies

| Service/Library        | Phase 4 Usage                                                         | Fallback if Unavailable                                          |
|------------------------|-----------------------------------------------------------------------|------------------------------------------------------------------|
| DigitalOcean           | Droplet, Managed PostgreSQL, Managed Redis, Backups                   | Hetzner or OVH as alternative cloud providers                    |
| Laravel Forge          | Server provisioning, Nginx, deploy pipeline, SSL                      | Manual server setup with Nginx + Certbot + Supervisor            |
| Twilio (production)    | Real SMS delivery for pilot businesses                                | Vonage with new TwilioSmsService implementation                  |
| Stripe (live or test)  | Real subscription billing (or test mode for pilot)                    | Manual invoicing for first 3 pilot businesses if live not ready  |
| Sentry                 | Exception tracking, error alerting                                    | Laravel log file + manual daily review during pilot              |
| UptimeRobot            | Uptime monitoring and alerting                                        | Better Uptime free tier; manual health check cron fallback       |
| `@sentry/vue`          | Frontend JS error tracking                                            | Console.error logging with manual review                         |

---

## Assumptions

> The following assumptions were made during spec generation. Review and adjust before Phase 4 begins.

- DigitalOcean is confirmed as cloud provider (ADR-008 recommended, TBD in PRD)
- Laravel Forge is used for server management (standard for Laravel + DigitalOcean)
- Domain `zeronoshow.fr` is registered and DNS is controllable
- Stripe live mode keys available before end of Phase 4 (or pilot runs on test mode)
- 3 pilot businesses identified and geographically local (in-person support possible during pilot week)
