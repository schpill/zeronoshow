# Phase 2 — Smart Reminders & Reliability Scoring

| Field            | Value                                                                                               |
|------------------|-----------------------------------------------------------------------------------------------------|
| **Phase**        | 2 of 4                                                                                              |
| **Name**         | Smart Reminders & Reliability Scoring                                                               |
| **Duration**     | Week 3 (1 week)                                                                                     |
| **Milestone**    | M2 — End-to-end flow: reservation → reminder fires at correct time based on score → auto-cancel if no response |
| **PRD Sections** | §5 (US-03, US-04, US-08), §7 (Features 2, 3), §9 (customers table updates)                        |
| **Prerequisite** | Phase 1 fully completed and validated (M1 milestone achieved, all exit criteria met)                |
| **Status**       | Not started                                                                                         |

---

## Section 1 — Phase Objectives

| ID       | Objective                                                                                                               | Verifiable?                                |
|----------|-------------------------------------------------------------------------------------------------------------------------|--------------------------------------------|
| P2-OBJ-1 | ReliabilityScoreService calculates correct score and tier from shows/no_shows counts                                    | Unit test passes                           |
| P2-OBJ-2 | Scheduler command dispatches 2h reminder for eligible reservations within ±5 minutes of trigger time                    | Feature test with time travel passes       |
| P2-OBJ-3 | Scheduler command dispatches 30min reminder for At Risk clients within ±2 minutes of trigger time                      | Feature test with time travel passes       |
| P2-OBJ-4 | Auto-cancellation fires when unconfirmed past deadline; reservation status = `cancelled_no_confirmation`                | Feature test passes                        |
| P2-OBJ-5 | Twilio delivery webhook updates `sms_logs.status` and `sms_logs.cost_eur`                                              | Feature test passes                        |
| P2-OBJ-6 | RecalculateReliabilityScore job fully updates customer score and score tier after status change                         | Unit + feature test passes                 |
| P2-OBJ-7 | Phone-verified flow: no verification SMS, reminder scheduled based on score tier                                        | Feature test passes (validated Phase 1, confirmed Phase 2) |
| P2-OBJ-8 | Score visible in reservation creation response; score updates within 5 minutes of no-show marking                      | Feature test passes                        |

---

## Section 2 — Entry Criteria

- Phase 1 exit criteria all checked (11 BE test files green, 5 FE test files green, CI pipeline green)
- Real SMS confirmed deliverable to French mobile via Twilio in staging environment
- Docker Compose stack stable
- `queue:work` processing jobs reliably in staging

---

## Section 3 — Scope — Requirement Traceability

| PRD Requirement Group                       | IDs in PRD            | Status   | Notes                                                       |
|---------------------------------------------|-----------------------|----------|-------------------------------------------------------------|
| Reliability score calculation               | US-04, Feature 2      | Included | Full implementation replacing Phase 1 stub                  |
| Score-based reminder strategy               | US-03, Feature 3      | Included | Three tiers: reliable/average/at_risk                       |
| Scheduler: 2h reminder                      | US-03, Feature 3      | Included | Fires for average + at_risk clients                         |
| Scheduler: 30min reminder                   | US-03, Feature 3      | Included | Fires for at_risk clients only                              |
| Auto-cancellation on confirmation deadline  | Feature 3, §12        | Included | Unconfirmed reminders auto-cancel                           |
| Twilio delivery status webhook              | US-08, §11            | Included | Full HMAC validation + sms_logs update                      |
| STOP opt-out handling                       | §9 Security           | Included | Handle STOP reply via Twilio webhook, mark customer opted_out |
| Score display in Vue SPA                    | US-04                 | Included | Score badge updates after status change via poll            |
| Advanced analytics                          | §4 Out of Scope       | No       | Deferred to Phase 3+                                        |
| Waitlist system                             | §4 Out of Scope       | No       | Deferred to V2                                              |

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
2. **Dark mode sur chaque composant** — variantes `dark:` incluses dès le premier commit. Mappings de référence dans `template_backoffice_client.html`.
3. **Polices via Google Fonts** — classes utilitaires de `polices.md` uniquement. Pour ce sprint : `.text-badge` sur tous les badges de statut et de fiabilité, `.text-label` sur les labels de formulaire.
4. **Badges de statut** — les 7 couleurs de statut (`pending_verification`, `pending_reminder`, `confirmed`, `cancelled_by_client`, `cancelled_no_confirmation`, `no_show`, `show`) sont définies dans `colors.md` §Statuts de réservation. Ne pas inventer de couleur.
5. **Badges de fiabilité** — les 3 tiers (`reliable`, `average`, `at_risk`) ont leurs couleurs exactes (fond/texte/bordure Tailwind) dans `colors.md` §Score de fiabilité.
6. **Templates HTML = spec visuelle de facto** — voir `template_backoffice_client.html` section tableau des réservations pour le rendu attendu des badges inline.

---

## Section 4 — Sprint Breakdown

### 4.2 Sprint 2 — Reminders & Scoring (Week 3)

#### 4.2.1 Sprint Objectives

- `ReliabilityScoreService` correctly computes score and tier from customer record
- `RecalculateReliabilityScore` job replaces Phase 1 stub with full implementation
- `ReservationObserver` triggers score recalculation on every terminal status change
- `ProcessScheduledReminders` command queries and dispatches reminder jobs accurately
- `AutoCancelExpiredReservations` command sets `cancelled_no_confirmation` for timed-out reservations
- `SendReminderSms` job sends correct body per tier and updates `reminder_Xh_sent` flag
- Twilio webhook validates HMAC, updates `sms_logs.status + cost_eur`, handles STOP reply
- Score badge in Vue SPA reflects current score tier via 30-second polling

---

#### 4.2.2 Database Migrations

| Migration name                                    | Description                                                                                                                                                                                                                                  |
|---------------------------------------------------|----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `2026_03_19_000001_add_opted_out_to_customers`    | Add `opted_out BOOLEAN NOT NULL DEFAULT false` to `customers` table. Add `opted_out_at TIMESTAMPTZ NULLABLE`. Indexes: none (low cardinality flag). |
| `2026_03_19_000002_add_score_tier_to_customers`   | Add `score_tier VARCHAR(20) NULLABLE` to `customers` (values: `reliable`, `average`, `at_risk`). Stored for fast reads without recomputing. Kept in sync by `RecalculateReliabilityScore` job. |

---

#### 4.2.3 Back-end Tasks

| ID         | Task                                                                                                                                                                                                                                                                                                                                                                               | PRD Ref          |
|------------|------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|------------------|
| P2-BE-001  | Create `app/Services/ReliabilityScoreService.php`. Method `recalculate(Customer $customer): Customer`: computes `score = shows_count / (shows_count + no_shows_count) * 100` (return null if both 0). Computes `score_tier`: `reliable` if ≥ 90, `average` if 70–89, `at_risk` if < 70 or null. Updates `customer.reliability_score`, `customer.score_tier`, `customer.last_calculated_at = now()`. Saves and returns customer. Method `getTierForScore(?float $score): string` (static, pure, testable).                     | US-04, Feature 2 |
| P2-BE-002  | Implement `app/Jobs/RecalculateReliabilityScore.php` (replace Phase 1 stub). Accepts `string $customerId`. `handle(ReliabilityScoreService $service)`: fetch Customer, call `$service->recalculate($customer)`. Invalidate Redis cache key `dashboard:{business_id}:*` (use Redis `keys` pattern or store known keys). `$tries = 3`, `backoff = [30, 60, 120]`.                   | US-04, Feature 2 |
| P2-BE-003  | Create `app/Observers/ReservationObserver.php`. Register in `AppServiceProvider`. `updated(Reservation $reservation)`: if `$reservation->isDirty('status')` AND new status is terminal (`confirmed`, `cancelled_by_client`, `cancelled_no_confirmation`, `no_show`, `show`) → dispatch `RecalculateReliabilityScore::dispatch($reservation->customer_id)`. Also update `customers.reservations_count`, `shows_count`, `no_shows_count` atomically using DB increment: `no_show` → increment `no_shows_count`; `show`/`confirmed` (end of appointment) → increment `shows_count`.                                         | Feature 2, US-04 |
| P2-BE-004  | Create `app/Console/Commands/ProcessScheduledReminders.php`. Artisan command `reminders:process`. Logic: (1) **2h reminder window**: query `reservations` WHERE status IN (`pending_reminder`, `confirmed`) AND `reminder_2h_sent = false` AND `scheduled_at` BETWEEN `now() + 1h55m` AND `now() + 2h05m`. For each: dispatch `SendReminderSms::dispatch($reservation, '2h')`. (2) **30min reminder window**: same query but `scheduled_at` BETWEEN `now() + 25m` AND `now() + 35m` AND `reminder_30m_sent = false` AND customer score_tier = `at_risk` (or score null). Dispatch `SendReminderSms::dispatch($reservation, '30m')`. Use DB transaction per batch to avoid double-dispatch on concurrent runs (pessimistic lock with `lockForUpdate()`). | US-03, Feature 3 |
| P2-BE-005  | Create `app/Console/Commands/AutoCancelExpiredReservations.php`. Command `reservations:auto-cancel`. Logic: (1) **Expired verification tokens**: `reservations` WHERE status = `pending_verification` AND `token_expires_at < now()` → bulk update status = `cancelled_no_confirmation`. (2) **No-confirmation after 30min reminder**: `reservations` WHERE status IN (`pending_reminder`, `confirmed`) AND `reminder_30m_sent = true` AND `scheduled_at < now() - 15min` AND status NOT IN (`cancelled_*`, `no_show`, `show`) → bulk update status = `cancelled_no_confirmation`. Log count of cancelled per run.                                                              | Feature 3        |
| P2-BE-006  | Configure `app/Console/Kernel.php` scheduler. Schedule `reminders:process` every minute: `$schedule->command('reminders:process')->everyMinute()->withoutOverlapping(10)`. Schedule `reservations:auto-cancel` every minute: same. Schedule `sms-logs:purge` daily at 03:00 (stub command, full impl Phase 4).                                                                    | Feature 3, §9    |
| P2-BE-007  | Create `app/Jobs/SendReminderSms.php`. Accepts `Reservation $reservation`, `string $type` (`2h` or `30m`). `handle(SmsServiceInterface $sms)`: (1) Re-fetch reservation — abort if status is terminal or reminder already sent. (2) Build body by type: `2h` → `"Bonjour {name}, rappel: votre RDV est demain à {time} chez {business}. Confirmez: {url}"` if Average tier; `"Bonjour {name}, rappel URGENT: votre RDV est dans 2h chez {business}. Confirmez impérativement: {url} ou annulez: {cancel_url}"` if At Risk. `30m` → `"Dernier rappel: votre RDV dans 30 min chez {business}. Confirmez: {url}"`. (3) Call `$sms->send()`, create SmsLog type `reminder_2h` or `reminder_30m`. (4) Update `reservation.reminder_2h_sent` or `reminder_30m_sent = true`. `$tries = 2`.  | US-03, Feature 3 |
| P2-BE-008  | Implement `app/Http/Controllers/Webhook/TwilioWebhookController.php` (replace stub). Method `handle(Request $request): Response`. (1) Validate HMAC: `app(SmsServiceInterface::class)->validateWebhookSignature($request)` → return 403 if invalid. (2) Parse payload: `MessageSid`, `MessageStatus`, `Price`, `PriceCurrency`. (3) Find SmsLog by `twilio_sid`. (4) Map Twilio status to internal: `delivered→delivered`, `failed/undelivered→failed`. (5) Update `sms_logs.status`, `sms_logs.cost_eur` (convert to EUR if price in USD using fixed rate 0.93 or store raw), `sms_logs.delivered_at` or `sms_logs.error_message`. (6) Handle STOP reply: if `Body = 'STOP'` (case-insensitive) → find Customer by `To` phone → set `opted_out = true`, `opted_out_at = now()`. Return 200.                     | US-08, §11       |
| P2-BE-009  | Update `app/Jobs/SendVerificationSms.php` and `SendReminderSms.php` to check `customer.opted_out = true` before sending → abort job silently (log + return without throwing) to avoid wasted SMS charges.                                                                                                                                                                         | §9 GDPR          |
| P2-BE-010  | Update `ReservationController::store` to regenerate confirmation token after phone-verified flag: when `phone_verified = true` → still generate `confirmation_token` and `token_expires_at` (needed for reminder confirmation links). The verification SMS is simply not sent.                                                                                                     | Feature 1, US-01 |
| P2-BE-011  | Update `ReservationController::show` and `ReservationResource` to include `customer.score_tier` and `customer.opted_out` in response payload. Update `CustomerController::lookup` to also return `opted_out`.                                                                                                                                                                     | US-04            |
| P2-BE-012  | Add `PATCH /api/v1/reservations/{id}/status` endpoint to `ReservationController`. Method `updateStatus(UpdateReservationStatusRequest $request, Reservation $reservation): JsonResponse`. Validate: `status` required, in:`show`,`no_show`. Policy: business_id must match authenticated business. Update `reservation.status`, `status_changed_at`. Observer triggers score recalculation. Return updated reservation + updated customer score. Add undo-window check: if previous status was set within 30 min AND new status would undo it → allow (no restriction needed, just update).                                     | US-04, §11       |
| P2-BE-013  | Create `app/Http/Requests/UpdateReservationStatusRequest.php`. Rules: `status` (required, string, in:show,no_show).                                                                                                                                                                                                                                                               | §11              |

---

#### 4.2.4 Back-end Tests (TDD)

| Test File                                                   | Test Cases                                                                                                                                                                                                                                                                |
|-------------------------------------------------------------|---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `tests/Unit/Services/ReliabilityScoreServiceTest.php`       | score is null when shows=0 and no_shows=0; score=100 when shows=5 no_shows=0; score=80 when shows=4 no_shows=1; tier reliable for score≥90; tier average for score 70–89; tier at_risk for score<70; tier at_risk for null score; updates customer record in DB           |
| `tests/Unit/Jobs/RecalculateReliabilityScoreTest.php`       | calls ReliabilityScoreService with correct customer; updates customer score_tier in DB; invalidates Redis cache key; retries on service exception                                                                                                                          |
| `tests/Unit/Observers/ReservationObserverTest.php`          | dispatches RecalculateReliabilityScore when status changes to no_show; dispatches on show; dispatches on confirmed; does NOT dispatch on non-terminal status changes; increments customer no_shows_count on no_show; increments shows_count on show                        |
| `tests/Feature/Commands/ProcessScheduledRemindersTest.php`  | dispatches 2h reminder for average-tier client 2h before appointment; dispatches 2h AND 30m reminder for at_risk client; does NOT dispatch for reliable-tier client; does NOT double-dispatch if reminder already sent; uses lockForUpdate to avoid concurrent dispatch; dispatches nothing when no reservations in window |
| `tests/Feature/Commands/AutoCancelExpiredTest.php`          | cancels reservations with expired tokens; cancels unconfirmed reservations 15min after appointment; does not cancel already-confirmed reservations; logs count of cancellations                                                                                            |
| `tests/Unit/Jobs/SendReminderSmsTest.php`                   | sends correct SMS body for average 2h reminder; correct body for at_risk 2h reminder; correct body for 30min reminder; marks reminder_2h_sent=true after send; marks reminder_30m_sent=true; aborts if customer opted_out; aborts if reservation already cancelled; aborts if reminder already sent |
| `tests/Feature/Webhook/TwilioWebhookTest.php`               | returns 403 with invalid HMAC signature; updates sms_log status to delivered on delivered event; updates sms_log status to failed on failed event; stores cost_eur from Price field; sets opted_out on STOP body; returns 200 for unknown twilio_sid (graceful ignore)     |
| `tests/Feature/Reservation/UpdateStatusTest.php`            | marks reservation as show and updates customer shows_count; marks as no_show and updates no_shows_count; returns 403 for wrong business; returns 422 for invalid status value; returns updated reliability_score in response                                               |

---

#### 4.2.5 Front-end Tasks

> **Référence design obligatoire** — Toutes les tâches frontend doivent strictement respecter :
> - **Couleurs** : `docs/graphics/colors.md` — statuts de réservation (7 états), score de fiabilité (3 tiers) avec leurs couleurs exactes (fond/texte/bordure Tailwind)
> - **Typographie** : `docs/graphics/polices.md` — classes `.text-badge` pour les badges de statut, `.text-label` pour les labels de formulaire
> - **Template de référence** : `docs/graphics/templates/template_backoffice_client.html` — voir la section tableau des réservations (badges statut, badge fiabilité inline, boutons d'action)
> - **Dark mode** : toutes les variantes `dark:` requises sur chaque composant (défini en Phase 1)

| ID         | Task                                                                                                                                                                                                                                                                                  | PRD Ref          |
|------------|---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|------------------|
| P2-FE-001  | Update `useReservations.js` composable: add `updateStatus(id, status)` action calling `PATCH /api/v1/reservations/{id}/status`. Returns updated reservation + customer score.                                                                                                        | US-04, §11       |
| P2-FE-002  | Create `resources/js/components/ReservationRow.vue`. Props: `reservation` object. Displays: customer_name, scheduled_at (formatted HH:mm), guests, `<ReliabilityBadge>`, status badge, action buttons. Action buttons: "Show" and "No-show" (calls `updateStatus`). Emit `updated(reservation)` on status change. Undo button appears for 30 minutes after marking No-Show (computed from `status_changed_at`).                  | US-04, Feature 5 |
| P2-FE-003  | Create `resources/js/components/StatusBadge.vue`. Props: `status: string`. Maps statuses to colors and French labels: `pending_verification→grey "À vérifier"`, `pending_reminder→blue "Confirmé (rappel à venir)"`, `confirmed→green "Confirmé"`, `cancelled_by_client→orange "Annulé"`, `cancelled_no_confirmation→red "Annulé (pas de réponse)"`, `no_show→red "No-show"`, `show→green "Présent"`. `aria-label` includes status label.  | Feature 5, US-06 |
| P2-FE-004  | Update `ReservationForm.vue`: on form submit success, emit `created` event AND update the parent list reactively (emit reservation data for immediate insertion without waiting for next poll).                                                                                        | US-01            |
| P2-FE-005  | Create `resources/js/composables/usePolling.js`. Accepts `fn: () => Promise`, `intervalMs: number`. Starts polling on `onMounted`, stops on `onUnmounted`. Exposes `start()`, `stop()`, `isPolling`. Used by Dashboard to refresh reservations every 30 seconds.                   | US-06, ADR-006   |
| P2-FE-006  | Create `resources/js/components/ReservationList.vue`. Props: `reservations[]`, `loading: bool`. Renders list of `<ReservationRow>` sorted by `scheduled_at`. Shows skeleton loaders when loading=true (3 placeholder rows). Empty state: "Aucune réservation pour cette journée." Handles `updated` events from rows to update local list reactively.                                | US-06, Feature 5 |

---

#### 4.2.6 Front-end Tests

| Test File                                                  | Test Cases                                                                                                                                                         |
|------------------------------------------------------------|--------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `tests/js/unit/composables/usePolling.spec.js`            | calls fn immediately on mount; repeats at correct interval; stops on unmount; handles fn rejection without crashing                                                |
| `tests/js/component/ReservationRow.spec.js`               | renders customer name, time, guests, status badge; show button calls updateStatus with 'show'; no-show button calls with 'no_show'; undo button visible within 30min window; undo button hidden after 30min; emits updated event |
| `tests/js/component/StatusBadge.spec.js`                  | renders correct French label for each status; correct color class for each status; aria-label present and correct                                                   |
| `tests/js/component/ReservationList.spec.js`              | renders correct number of ReservationRow children; shows skeleton on loading=true; shows empty state with no reservations; handles updated event and updates list  |

---

#### 4.2.7 Sprint Deliverables Checklist

- [ ] `php artisan reminders:process` dispatches correct jobs for reservations in 2h window
- [ ] `php artisan reminders:process` dispatches 30min reminder only for `at_risk` clients
- [ ] `php artisan reservations:auto-cancel` cancels expired token reservations
- [ ] 2h reminder SMS arrives on a real phone 2 hours before a test appointment
- [ ] Twilio webhook with valid HMAC updates `sms_logs.status` correctly
- [ ] Twilio webhook with invalid HMAC returns 403
- [ ] STOP reply sets `customer.opted_out = true`; subsequent SMS jobs are skipped
- [ ] `PATCH /api/v1/reservations/{id}/status` marks show/no-show and returns updated score
- [ ] ReservationRow renders with all status badges and action buttons
- [ ] No-show undo button visible within 30 minutes, hidden after
- [ ] Dashboard polls every 30 seconds and reflects new status without page reload
- [ ] All 8 test files pass with 0 failures
- [ ] CI pipeline green

---

## Section 5 — API Endpoints Delivered in Phase 2

| Method | Endpoint                              | Controller                             | Auth   | Notes                                                                                               |
|--------|---------------------------------------|----------------------------------------|--------|-----------------------------------------------------------------------------------------------------|
| PATCH  | /api/v1/reservations/{id}/status      | ReservationController::updateStatus    | Bearer | Body: {status: show\|no_show}. Returns {reservation, customer: {reliability_score, score_tier}}    |
| POST   | /api/v1/webhooks/twilio               | TwilioWebhookController::handle        | None   | HMAC validated. Updates sms_logs. Handles STOP opt-out. Returns 200.                               |

---

## Section 6 — Exit Criteria

| # | Criterion                                                                                               | Validated |
|---|---------------------------------------------------------------------------------------------------------|-----------|
| 1 | All 8 Phase 2 back-end test files pass                                                                  | [ ]       |
| 2 | All 4 Phase 2 front-end test files pass                                                                 | [ ]       |
| 3 | Scheduler fires 2h reminder for average + at_risk reservations in staging (verified by SMS on real device) | [ ]    |
| 4 | Scheduler fires 30min reminder only for at_risk reservations                                            | [ ]       |
| 5 | Auto-cancel sets `cancelled_no_confirmation` on expired unconfirmed reservations                        | [ ]       |
| 6 | Twilio webhook HMAC validation rejects tampered requests (403)                                          | [ ]       |
| 7 | Score updates within 5 minutes of marking a reservation as No-Show                                     | [ ]       |
| 8 | Opted-out customer does not receive SMS (job aborts silently)                                           | [ ]       |
| 9 | Back-end code coverage ≥ 80% on Phase 2 feature tests                                                  | [ ]       |
| 10 | CI pipeline green on main                                                                               | [ ]       |

---

## Section 7 — Risks Specific to Phase 2

| Risk                                                              | Probability | Impact | Mitigation                                                                                        |
|-------------------------------------------------------------------|-------------|--------|---------------------------------------------------------------------------------------------------|
| Scheduler runs twice concurrently, double-dispatching reminder jobs | Medium      | High   | `withoutOverlapping(10)` on scheduler + `lockForUpdate()` on query in command                     |
| Reminder SMS sent at wrong timezone (UTC vs Europe/Paris)         | Medium      | High   | Always convert `scheduled_at` from UTC to `business.timezone` when building SMS body             |
| Twilio STOP handling breaks E.164 lookup (international format)   | Low         | Medium | Normalise `From` field to E.164 before customer lookup; log unmatched numbers                    |
| RecalculateReliabilityScore job backlog during heavy usage        | Low         | Medium | Job is lightweight (1 DB update); monitor Horizon queue depth                                    |
| Token already consumed when reminder arrives (edge case)          | Low         | Low    | `SendReminderSms` generates a fresh token for reminder links if original token is null            |

---

## Section 8 — External Dependencies

| Service/Library         | Phase 2 Usage                                          | Fallback if Unavailable                        |
|-------------------------|--------------------------------------------------------|------------------------------------------------|
| Twilio                  | Reminder SMS delivery, delivery status webhook, STOP opt-out | Mock SmsServiceInterface in tests         |
| Redis                   | Queue backend for reminder jobs; cache invalidation    | QUEUE_CONNECTION=sync for testing only          |
| Laravel Scheduler (cron) | Fires reminders:process every minute                  | Manual artisan command invocation in emergency |
