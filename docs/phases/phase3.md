# Phase 3 — Dashboard & Billing

| Field            | Value                                                                                     |
|------------------|-------------------------------------------------------------------------------------------|
| **Phase**        | 3 of 4                                                                                    |
| **Name**         | Dashboard & Billing                                                                       |
| **Duration**     | Week 4 (1 week)                                                                           |
| **Milestone**    | M3 — Business can manage a full day of reservations from the dashboard and subscribe via Stripe |
| **PRD Sections** | §5 (US-06, US-07), §7 (Feature 5), §10 (Security), §11 (API), §7 Billing Architecture   |
| **Prerequisite** | Phase 2 fully completed and validated (M2 milestone achieved, all exit criteria met)      |
| **Status**       | Not started                                                                               |

---

## Section 1 — Phase Objectives

| ID       | Objective                                                                                                       | Verifiable?               |
|----------|-----------------------------------------------------------------------------------------------------------------|---------------------------|
| P3-OBJ-1 | Dashboard loads today's reservations in < 1 second for 100 reservations                                        | Performance test passes   |
| P3-OBJ-2 | Daily and weekly views display correct reservations with correct status badges                                  | Feature test passes       |
| P3-OBJ-3 | Business can mark show/no-show with undo available for 30 minutes                                               | Feature test passes       |
| P3-OBJ-4 | SMS cost per reservation and monthly total visible in dashboard                                                 | Feature test passes       |
| P3-OBJ-5 | Trial enforcement blocks reservation creation after 14 days with clear 402 response                            | Feature test passes       |
| P3-OBJ-6 | Stripe Checkout session created and redirects business to payment page                                          | Manual verification       |
| P3-OBJ-7 | Stripe webhook updates business subscription_status to `active` on successful payment                          | Feature test passes       |
| P3-OBJ-8 | Trial expiry email sent 48 hours before trial_ends_at via configured email provider                            | Feature test passes       |
| P3-OBJ-9 | Monthly SMS cost aggregated and Stripe Invoice Item created on 1st of each month                               | Feature test passes       |

---

## Section 2 — Entry Criteria

- Phase 2 exit criteria all checked (scheduler, reminders, scoring all validated)
- Stripe test mode keys available (`STRIPE_KEY`, `STRIPE_SECRET`, `STRIPE_WEBHOOK_SECRET`)
- Email provider configured (Resend.com or Mailpit for staging) — `MAIL_*` vars set
- Stripe webhook endpoint registered in Stripe test dashboard pointing to staging URL
- Laravel Horizon installed for queue monitoring (needed for billing job visibility)

---

## Section 3 — Scope — Requirement Traceability

| PRD Requirement Group                    | IDs in PRD              | Status   | Notes                                                        |
|------------------------------------------|-------------------------|----------|--------------------------------------------------------------|
| Full dashboard view (daily + weekly)     | US-06, Feature 5        | Included | Complete replacement of Phase 1 stub                         |
| Manual show/no-show marking with undo    | US-04, Feature 5        | Included | Undo window 30 minutes                                       |
| SMS cost display per reservation + monthly | §11 /api/v1/dashboard | Included |                                                              |
| Trial enforcement (402 on expiry)        | US-07, §5               | Included | RequireActiveSubscription middleware already stubbed Phase 1 |
| Stripe subscription checkout             | §7 Billing, US-07       | Included | Checkout session + success/cancel redirect                   |
| Stripe webhooks (activated, cancelled, payment_failed) | §7 Billing | Included |                                                             |
| Trial expiry email notification          | US-07, §13 Phase 3      | Included | 48h before trial_ends_at                                     |
| Monthly SMS billing via Stripe Invoice Items | §7 Billing, ADR-007 | Included | Cron job on 1st of month                                     |
| Advanced analytics                       | §4 Out of Scope         | No       | Deferred to V2                                               |
| Multi-location                           | §4 Out of Scope         | No       | Deferred to V2                                               |

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
| **Template backoffice client** | `docs/graphics/templates/template_backoffice_client.html` | Layout complet : sidebar w-60, header h-16, stats bar 4 colonnes, tableau réservations (7 badges statut + 3 badges fiabilité), formulaire rapide, synthèse mensuelle, **dark mode complet** avec mappings `dark:` exacts | Toutes les pages du backoffice client — référence principale de ce sprint |
| **Template backoffice admin** | `docs/graphics/templates/template_backoffice_zeronoshow.html` | Espace admin ZNS : sidebar slate-900, accent violet, tableau établissements, health panel, graphique SMS, logs d'activité | Référence pour interface interne ZeroNoShow |
| **Template site vitrine** | `docs/graphics/templates/template_site_vitrine.html` | Landing page : hero, features, pricing, CTA — palette Emerald sur blanc | Pages publiques, page confirmation client `/c/{token}` |

### Règles impératives

1. **Aucune couleur hors charte** — utiliser exclusivement les classes Tailwind définies dans `colors.md`. Emerald pour les actions principales, Slate pour fonds/textes neutres, couleurs sémantiques pour les états.
2. **Dark mode complet livré en Phase 3** — `DarkModeToggle.vue` (P3-FE-011) est la tâche centrale de ce sprint. Tous les composants livrés en Phase 3 doivent inclure leurs variantes `dark:`. Se référer à `template_backoffice_client.html` pour les mappings exacts (`dark:bg-slate-950`, `dark:bg-slate-900`, `dark:border-slate-800`, `dark:text-slate-50`, etc.).
3. **Dashboard** — `template_backoffice_client.html` est la référence pixel-perfect du Dashboard.vue complet : structure HTML, stats bar 4 colonnes (emerald/blue/amber/red), tableau réservations, formulaire rapide, synthèse mensuelle.
4. **TrialBanner** — couleur d'avertissement = amber (voir `colors.md` §Sémantiques). Variante rouge quand ≤ 3 jours : red-600/red-50.
5. **SubscriptionPage** — bouton CTA en `bg-emerald-600 hover:bg-emerald-700` (couleur principale de la charte). États success/cancel mappés sur couleurs sémantiques de `colors.md`.
6. **Logo dans NavBar** — `zeronoshow-light.svg` en mode clair, `zeronoshow-dark.svg` en mode sombre. Basculement géré par le même mécanisme que dans `template_backoffice_client.html`.

---

## Section 4 — Sprint Breakdown

### 4.3 Sprint 3 — Dashboard & Billing (Week 4)

#### 4.3.1 Sprint Objectives

- Full dashboard page renders reservations with daily/weekly toggle, stats, and SMS cost
- `ReservationRow` show/no-show actions and undo all work end-to-end
- Trial warning banner + 402 enforcement operational
- Stripe Checkout creates session and redirects
- Stripe webhooks activate/cancel subscription and update business record
- Trial expiry email dispatched 48h before expiry
- Monthly SMS cost job creates Stripe Invoice Items
- Laravel Horizon deployed in staging for queue visibility

---

#### 4.3.2 Database Migrations

| Migration name                                     | Description                                                                                                                             |
|----------------------------------------------------|-----------------------------------------------------------------------------------------------------------------------------------------|
| `2026_03_26_000001_add_horizon_to_failed_jobs`     | No schema change — ensure `failed_jobs` table exists (created by Laravel default migration). Add index on `failed_at` column (btree) for Horizon queries. |

---

#### 4.3.3 Back-end Tasks

| ID         | Task                                                                                                                                                                                                                                                                                                                                                                                          | PRD Ref            |
|------------|-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|--------------------|
| P3-BE-001  | Install Laravel Horizon: `composer require laravel/horizon`. Publish config + assets. Configure `config/horizon.php`: supervisor `worker` process with queue `default`, `tries=3`, `timeout=60`. Expose Horizon dashboard at `/horizon` — restrict to authenticated business with `admin` flag (or simply disable public access via `Horizon::auth()`). | §8 Observability   |
| P3-BE-002  | Install email library: `composer require resend/resend-laravel` (or configure SMTP for Mailpit in dev). Configure `config/mail.php` and `.env.example` with `MAIL_MAILER`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_FROM_ADDRESS=noreply@zeronoshow.fr`, `MAIL_FROM_NAME=ZeroNoShow`. Test with Mailpit locally.                                                                                     | US-07              |
| P3-BE-003  | Create `app/Mail/TrialExpiryWarning.php` Mailable. Constructor: `Business $business`. `envelope()`: subject `"Votre essai ZeroNoShow expire dans 48h"`. `content()`: Blade view `emails.trial-expiry` with: business name, trial_ends_at formatted, subscription CTA link (`/subscription`). `attachments()`: empty.                                                                        | US-07              |
| P3-BE-004  | Create `app/Console/Commands/SendTrialExpiryEmails.php`. Command `trial:expiry-emails`. Logic: query businesses WHERE `subscription_status = 'trial'` AND `trial_ends_at` BETWEEN `now() + 47h` AND `now() + 49h`. For each: dispatch `Mail::to($business->email)->queue(new TrialExpiryWarning($business))`. Log count. Schedule in Kernel: `hourly()` (checks every hour, email window prevents duplicate sends). | US-07              |
| P3-BE-005  | Create `app/Services/StripeService.php`. Method `createCheckoutSession(Business $business): string` (returns Checkout URL): instantiate `Stripe\Stripe::setApiKey(config('services.stripe.secret'))`. Create or retrieve Stripe Customer (use `$business->stripe_customer_id` if set, else create + save). Create Checkout Session: `mode=subscription`, price ID from env `STRIPE_PRICE_ID` (19€/month recurring), `success_url=/subscription/success`, `cancel_url=/subscription`. Return `$session->url`. Method `createInvoiceItem(Business $business, float $amountEur, string $description): void`: create Stripe `InvoiceItem` on customer with `amount` in cents, `currency=eur`, `description`.   | §7 Billing, ADR-007|
| P3-BE-006  | Create `app/Http/Controllers/SubscriptionController.php`. Method `checkout(Request $request): JsonResponse`: call `StripeService::createCheckoutSession()`. Return `{checkout_url: string}`. Method `show(Request $request): JsonResponse`: return `{subscription_status, trial_ends_at, stripe_customer_id, sms_cost_this_month}`. Add routes: `POST /api/v1/subscription/checkout` (Bearer), `GET /api/v1/subscription` (Bearer).          | §7 Billing, §11    |
| P3-BE-007  | Create `app/Http/Controllers/Webhook/StripeWebhookController.php`. Method `handle(Request $request): Response`. (1) Validate `Stripe-Signature` header via `\Stripe\Webhook::constructEvent($payload, $sig, config('services.stripe.webhook_secret'))` → return 400 on failure. (2) Switch on event type: `checkout.session.completed` → update `business.subscription_status = 'active'`, store `stripe_customer_id` and `stripe_subscription_id`. `customer.subscription.deleted` → `subscription_status = 'cancelled'`. `invoice.payment_failed` → log warning + send payment failure email (stub mail). Return 200.                              | §7 Billing         |
| P3-BE-008  | Create `app/Console/Commands/SyncMonthlySmsCost.php`. Command `billing:sync-sms-cost`. Logic: runs on 1st of month. For each active business: sum `sms_logs.cost_eur` WHERE `created_at` in previous calendar month AND `status = 'delivered'`. If total > 0: call `StripeService::createInvoiceItem($business, $total, "SMS ZeroNoShow – {month}")`. Log result per business. Schedule in Kernel: `monthlyOn(1, '06:00')`. | §7 Billing, ADR-007|
| P3-BE-009  | Update `app/Http/Controllers/DashboardController.php` (create if not exists from Phase 1). Method `index(Request $request): JsonResponse`. Query params: `date` (default today), `week`. Returns: `{reservations: ReservationResource[], stats: {confirmed, pending_verification, pending_reminder, cancelled, no_show, show, total}, sms_cost_this_month: float, weekly_no_show_rate: float\|null}`. `weekly_no_show_rate` = no_shows / total for rolling 7 days. Cache entire response in Redis key `dashboard:{business_id}:{date}` TTL 30 seconds. Route: `GET /api/v1/dashboard` (Bearer).                          | US-06, §11         |
| P3-BE-010  | Update `RequireActiveSubscription.php` middleware (replace Phase 1 stub with full logic): check `$business->isOnActivePlan()`. If false: return 402 JSON `{"error":{"code":"SUBSCRIPTION_REQUIRED","message":"Votre période d'essai est terminée. Abonnez-vous pour continuer."}}`. Ensure middleware is applied only to `POST /reservations` (not GET endpoints — businesses should still be able to view their data after trial expiry).                                                                                                             | US-07, §10         |
| P3-BE-011  | Create `resources/views/emails/trial-expiry.blade.php`. Plain text + HTML version. Content: greeting with business name, expiry date/time formatted in `Europe/Paris` timezone, link to `/subscription`, unsubscribe note.                                                                                                                                                                    | US-07              |

---

#### 4.3.4 Back-end Tests (TDD)

| Test File                                                  | Test Cases                                                                                                                                                                                                                           |
|------------------------------------------------------------|--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `tests/Feature/Billing/StripeCheckoutTest.php`             | returns checkout_url for active trial business; creates Stripe customer if not exists; reuses existing stripe_customer_id; returns 401 for unauthenticated request                                                                   |
| `tests/Feature/Webhook/StripeWebhookTest.php`              | updates subscription_status to active on checkout.session.completed; stores stripe_customer_id; updates subscription_status to cancelled on subscription.deleted; logs warning on invoice.payment_failed; returns 400 on invalid signature |
| `tests/Feature/Billing/SyncSmsCostTest.php`                | creates Stripe invoice item for business with delivered SMS in previous month; skips business with zero SMS cost; skips cancelled businesses; uses correct amount from sms_logs.cost_eur sum                                          |
| `tests/Feature/Commands/TrialExpiryEmailTest.php`          | sends email to business with trial expiring in 48h; does not send to business with trial expiring in 72h; does not send to already active business; does not send duplicate if command runs twice in same hour window                |
| `tests/Feature/Dashboard/DashboardIndexTest.php`           | returns reservations filtered by date; returns correct stats object; returns sms_cost_this_month sum; returns weekly_no_show_rate; response cached in Redis for 30s; cache invalidated after reservation status update; loads in < 500ms for 100 reservations (using SQLite in-memory) |
| `tests/Feature/Subscription/SubscriptionShowTest.php`      | returns subscription_status and trial_ends_at; returns sms_cost_this_month; returns 401 for unauthenticated                                                                                                                          |
| `tests/Feature/Middleware/RequireSubscriptionTest.php`     | blocks POST /reservations with 402 for expired trial; allows POST for active trial; allows POST for active subscription; allows GET /reservations for expired trial (read-only access preserved)                                     |

---

#### 4.3.5 Front-end Tasks

> **Référence design obligatoire** — Toutes les tâches frontend doivent strictement respecter :
> - **Couleurs** : `docs/graphics/colors.md` — cartes stats (emerald/blue/amber/red), badges fiabilité, barre de progression mensuelle
> - **Typographie** : `docs/graphics/polices.md` — `.text-heading-1` pour le titre de page, `.text-caption` pour les metadata (date, coût SMS), `.text-badge` pour les statuts
> - **Template de référence** : `docs/graphics/templates/template_backoffice_client.html` — structure complète du dashboard (sidebar, header h-16, stats bar 4 colonnes, tableau réservations, formulaire rapide, synthèse mensuelle)
> - **Dark mode** : P3-FE-011 livre le toggle et les variantes `dark:` sur tous les composants existants — intégrer dès la conception de chaque nouveau composant

| ID         | Task                                                                                                                                                                                                                                                                                                  | PRD Ref          |
|------------|-------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|------------------|
| P3-FE-001  | Replace stub `Dashboard.vue` with full implementation. Layout follows `docs/graphics/templates/template_backoffice_client.html`: sidebar (w-60, white/dark:slate-900), sticky header (h-16), main content area (p-6, space-y-6). Sections: page title "Réservations", date navigator, daily/weekly toggle, `<StatsBar>`, `<ReservationList>` with `usePolling(30000)`, `<ReservationForm>` in slide-over. All components must include `dark:` Tailwind variants throughout. On `created` event: insert reservation immediately + invalidate poll. | US-06, Feature 5 |
| P3-FE-002  | Create `resources/js/components/StatsBar.vue`. Props: `stats: {confirmed, pending_verification, pending_reminder, cancelled, no_show, show, total}`. Renders 4 stat cards: Confirmées (green), En attente (blue), Annulées (orange), No-shows (red). Responsive: 2×2 grid on mobile, 4-column row on desktop. Each card shows count and label. `role="status"` for accessibility.                   | US-06            |
| P3-FE-003  | Create `resources/js/components/DateNavigator.vue`. Props: `modelValue: string` (ISO date). Emits `update:modelValue`. Renders: left arrow (prev day), date display `Jeudi 12 mars 2026`, right arrow (next day), "Aujourd'hui" button (disabled when on today). Keyboard accessible (arrows as buttons with `aria-label`). Used by Dashboard.vue.                                                  | US-06            |
| P3-FE-004  | Create `resources/js/pages/SubscriptionPage.vue`. Route `/subscription`. Shows: current subscription status (trial / active / cancelled), trial_ends_at formatted, monthly SMS cost. CTA button "S'abonner - 19€/mois": calls `POST /api/v1/subscription/checkout` → redirects to `checkout_url`. Success redirect page: show "Votre abonnement est actif" confirmation. Cancel redirect: return to dashboard with toast.   | US-07, §7 Billing|
| P3-FE-005  | Create `resources/js/components/TrialBanner.vue`. Shown in `AppLayout.vue` when `daysUntilTrialEnd ≤ 7`. Content: `"Votre essai expire dans X jours — Abonnez-vous"` with link to `/subscription`. Dismissable (sessionStorage). Red variant when ≤ 2 days. `role="alert"` for accessibility.            | US-07            |
| P3-FE-006  | Create `resources/js/pages/ReservationDetailPage.vue`. Route `/reservations/:id`. Shows: all reservation fields, customer reliability badge + score history summary, SMS logs table (type, status, cost, sent_at). Action buttons: mark show/no-show (with undo timer). Back link to dashboard.       | US-04, Feature 5 |
| P3-FE-007  | Create `resources/js/components/SmsLogTable.vue`. Props: `logs: SmsLog[]`. Renders table: Type (badge), Status (badge), Cost (€), Sent at (formatted), Delivered at. Empty state: "Aucun SMS envoyé". Status badges: queued=grey, sent=blue, delivered=green, failed=red.                            | US-08            |
| P3-FE-008  | Update `NavBar.vue`: add link to subscription page with status indicator (green dot = active, orange dot = trial X days, red dot = expired). Add `/reservations/:id` as named route in router.                                                                                                        | US-07            |
| P3-FE-009  | Create `resources/js/composables/useSubscription.js`. Calls `GET /api/v1/subscription`. Returns `{status, trial_ends_at, sms_cost_this_month, daysUntilTrialEnd}`. Used by AppLayout and SubscriptionPage.                                                                                           | US-07            |
| P3-FE-010  | Add weekly view to `Dashboard.vue`. When weekly toggle active: call `GET /api/v1/reservations?week=YYYY-WNN`. Group reservations by day. Render 7 daily sections, each with `<ReservationList>`. Display total no-show rate for the week from stats. Toggle is a pill button group (Tailwind `rounded-l-full` / `rounded-r-full` pattern).  | US-06            |
| P3-FE-011  | Implement dark mode across the entire SPA (client backoffice). (1) Ensure `tailwind.config.js` has `darkMode: 'class'` (set in P1-FE-002). (2) Create `resources/js/components/DarkModeToggle.vue`: button with sun/moon SVG icons (sun visible in dark, moon in light, each via `hidden dark:block` / `block dark:hidden`). Emits no event — toggles `document.documentElement.classList` directly and persists to `localStorage` key `zns-theme`. (3) Add anti-flash script to SPA `index.html` `<head>` (reads `zns-theme` or `prefers-color-scheme` before first paint). (4) Add `dark:` variants to ALL components: AppLayout, NavBar, sidebar, header, cards (`bg-white dark:bg-slate-900`), borders (`border-slate-200 dark:border-slate-800`), text (`text-slate-900 dark:text-slate-50`), inputs (`bg-white dark:bg-slate-800`), hover rows (`hover:bg-slate-50 dark:hover:bg-slate-800/50`). Reference: `docs/graphics/templates/template_backoffice_client.html` for exact dark: class mapping. | Design System, docs/graphics/colors.md |

---

#### 4.3.6 Front-end Tests

| Test File                                                  | Test Cases                                                                                                                                                                        |
|------------------------------------------------------------|-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `tests/js/component/StatsBar.spec.js`                     | renders correct counts for each category; green/blue/orange/red color classes; role=status present; handles zero values                                                           |
| `tests/js/component/DateNavigator.spec.js`                | prev arrow decrements date; next arrow increments; today button emits today's date; today button disabled when already on today; keyboard-accessible buttons                      |
| `tests/js/component/TrialBanner.spec.js`                  | visible when ≤7 days; red variant when ≤2 days; hidden when dismissed (sessionStorage); not shown when subscription active; link to /subscription present                        |
| `tests/js/component/SmsLogTable.spec.js`                  | renders correct row count; correct status badge colors; cost formatted as €; empty state shown with no logs                                                                       |
| `tests/js/unit/composables/useSubscription.spec.js`       | calls GET /subscription; returns daysUntilTrialEnd computed from trial_ends_at; handles active and cancelled status                                                               |
| `tests/js/pages/Dashboard.spec.js`                        | renders StatsBar with stats; renders ReservationList; date navigation changes date param; weekly toggle switches view; new reservation from form inserts into list immediately    |
| `tests/js/unit/components/DarkModeToggle.spec.js`         | clicking toggle adds 'dark' class to documentElement; clicking again removes 'dark' class; preference saved to localStorage as 'dark'/'light'; sun icon visible in dark mode; moon icon visible in light mode; does not flash on mount if localStorage has saved preference |

---

#### 4.3.7 Sprint Deliverables Checklist

- [ ] Dashboard loads in < 1 second for 100 reservations (measured in staging)
- [ ] Daily view shows correct reservations; weekly view groups by day correctly
- [ ] Stats bar counts match actual reservation statuses
- [ ] "S'abonner" button creates Stripe Checkout session and redirects (test mode)
- [ ] Stripe webhook activates subscription and updates dashboard status indicator
- [ ] Trial expiry email received in Mailpit 48h before expiry (simulated with test)
- [ ] Monthly SMS cost cron creates Stripe Invoice Item for previous month
- [ ] POST /reservations returns 402 for expired trial; GET /reservations still works
- [ ] Trial warning banner appears with correct days remaining
- [ ] ReservationDetailPage shows SMS logs with costs
- [ ] All 7 BE test files pass
- [ ] All 7 FE test files pass
- [ ] Dark mode toggle persists theme in localStorage; `dark` class applied on `<html>` without flash
- [ ] CI pipeline green

---

## Section 5 — API Endpoints Delivered in Phase 3

| Method | Endpoint                          | Controller                           | Auth   | Notes                                                                                           |
|--------|-----------------------------------|--------------------------------------|--------|-------------------------------------------------------------------------------------------------|
| GET    | /api/v1/dashboard                 | DashboardController::index           | Bearer | Query: ?date=YYYY-MM-DD. Returns {reservations[], stats{}, sms_cost_this_month, weekly_no_show_rate} |
| POST   | /api/v1/subscription/checkout     | SubscriptionController::checkout     | Bearer | Returns {checkout_url}. Creates Stripe Checkout session (19€/month)                            |
| GET    | /api/v1/subscription              | SubscriptionController::show         | Bearer | Returns {subscription_status, trial_ends_at, sms_cost_this_month}                              |
| POST   | /api/v1/webhooks/stripe           | StripeWebhookController::handle      | None   | Stripe-Signature validated. Handles checkout.completed, subscription.deleted, payment_failed    |

---

## Section 6 — Exit Criteria

| # | Criterion                                                                                                        | Validated |
|---|------------------------------------------------------------------------------------------------------------------|-----------|
| 1 | All 7 Phase 3 back-end test files pass                                                                           | [ ]       |
| 2 | All 7 Phase 3 front-end test files pass                                                                          | [ ]       |
| 3 | Dashboard endpoint responds in < 500ms for 100 reservations in staging (measured with `curl -w "%{time_total}"`) | [ ]       |
| 4 | Stripe test-mode checkout flow completes and sets subscription_status to `active`                                | [ ]       |
| 5 | Trial expiry email rendered correctly in Mailpit with correct date and CTA link                                  | [ ]       |
| 6 | POST /reservations returns 402 JSON error for business with expired trial                                        | [ ]       |
| 7 | Laravel Horizon dashboard accessible and showing queue workers in staging                                        | [ ]       |
| 8 | CI pipeline green on main                                                                                        | [ ]       |
| 9 | Back-end code coverage ≥ 80% on Phase 3 feature tests                                                           | [ ]       |
| 10 | Dark mode: toggling applies `dark` class on `<html>`; preference persists across reload; no flash on load       | [ ]       |

---

## Section 7 — Risks Specific to Phase 3

| Risk                                                            | Probability | Impact | Mitigation                                                                                    |
|-----------------------------------------------------------------|-------------|--------|-----------------------------------------------------------------------------------------------|
| Stripe webhook not reaching staging server (no public URL)      | High        | High   | Use Stripe CLI `stripe listen --forward-to` for local dev; ngrok for staging                 |
| Monthly SMS billing cron runs on wrong month boundary           | Medium      | Medium | Test with explicit month parameter; log business_id + amount for every invoice item created  |
| Duplicate Stripe Invoice Items if cron runs twice in same month | Low         | High   | Add idempotency key: `"sms-cost-{business_id}-{YYYY-MM}"` to Invoice Item metadata; check for existing before creating |
| Dashboard Redis cache stale after manual status update          | Medium      | Low    | Invalidate `dashboard:{business_id}:*` on any reservation status change (already handled by ReservationObserver in Phase 2) |
| Email provider rate limit for trial expiry batch                | Low         | Low    | ≤100 businesses in Year 1; queue emails instead of sending synchronously                     |

---

## Section 8 — External Dependencies

| Service/Library      | Phase 3 Usage                                            | Fallback if Unavailable                                      |
|----------------------|----------------------------------------------------------|--------------------------------------------------------------|
| Stripe               | Subscription checkout, webhook events, Invoice Items     | Manual invoicing for first 20 clients if Stripe setup delayed |
| Resend.com / SMTP    | Trial expiry email delivery                              | Mailpit for local/staging; log email content if provider down |
| Laravel Horizon      | Queue monitoring dashboard                               | `php artisan queue:monitor` CLI fallback                     |
| Redis                | Dashboard cache (30s TTL)                                | Remove cache layer — direct DB query (acceptable at this scale) |
