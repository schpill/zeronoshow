# Phase 8 — Automatic Voice Calls

| Field            | Value                                                                                              |
|------------------|----------------------------------------------------------------------------------------------------|
| **Phase**        | 8 of 10                                                                                             |
| **Name**         | Automatic Voice Calls — Twilio Voice with prepaid credits for high-stakes reservations             |
| **Duration**     | Weeks 19–22 (4 weeks)                                                                               |
| **Milestone**    | M8 — Voice confirmation rate ≥ 70% on called reservations across pilot businesses                 |
| **PRD Sections** | §Leo PRD V2 — automatic client call; PRD §2 no-show prevention                                    |
| **Prerequisite** | Phase 7 fully completed and validated (waitlist operational, ReservationObserver stable)           |
| **Status**       | Not started                                                                                         |

---

## Section 1 — Phase Objectives

| ID        | Objective                                                                                                                             | Verifiable?                          |
|-----------|---------------------------------------------------------------------------------------------------------------------------------------|--------------------------------------|
| P8-OBJ-1  | A business owner can activate automatic voice calling for reservations matching configurable criteria (score threshold, party size)    | Feature test passes                  |
| P8-OBJ-2  | When triggered, Twilio calls the client phone and plays a French voice message inviting confirmation via DTMF keypress                | Feature test + integration test passes |
| P8-OBJ-3  | Client pressing 1 confirms the reservation; pressing 2 cancels it; no answer retries up to 3 times with configurable delay            | Feature test passes                  |
| P8-OBJ-4  | Voice credit wallet operates on the prepaid model (same as WhatsApp Phase 6): cap, auto-renew, deduction per call, suspend at 0      | Unit test passes                     |
| P8-OBJ-5  | Business owner can initiate a manual voice call from the reservation detail page                                                      | Feature test passes                  |
| P8-OBJ-6  | All voice call attempts are logged in `voice_call_logs` with duration, cost, and outcome                                              | Feature test passes                  |
| P8-OBJ-7  | Dashboard shows voice call log per reservation; credit card shows balance and top-up flow                                             | E2E test passes                      |
| P8-OBJ-8  | Backend test coverage ≥ 80%, frontend ≥ 80%                                                                                          | CI coverage gate passes              |

---

## Section 2 — Entry Criteria

- Phase 7 exit criteria all validated (waitlist live, CI green)
- Twilio account with Voice capability enabled (same account as SMS)
- `TWILIO_TWIML_APP_SID` or direct TwiML URL registered in Twilio Console
- Stripe operational (Phase 3) for voice credit top-up
- `CLAUDE.md` updated to reference Phase 8 tasks

---

## Section 3 — Scope — Requirement Traceability

| Requirement Group                        | Source Ref                    | Included?  | Notes                                                                                           |
|------------------------------------------|-------------------------------|------------|-------------------------------------------------------------------------------------------------|
| Automatic voice call on high-risk reservation | Leo PRD §6, conversation | Yes        | Full — triggers on score threshold OR party size OR manual. DTMF confirm/cancel.              |
| TwiML voice message (French)             | Conversation                  | Yes        | Twilio TwiML `<Say>` with voice `Polly.Léa` (AWS Polly French female voice via Twilio).       |
| DTMF response handling                   | Conversation                  | Yes        | `<Gather>` verb — press 1 (confirm), press 2 (cancel), no input → retry or mark no_answer.   |
| Call retry on no-answer                  | Conversation                  | Yes        | Up to 3 attempts, configurable delay (default 10 min between retries).                        |
| Prepaid credit wallet (same as Phase 6)  | Conversation                  | Yes        | Separate `voice_credit_cents` on businesses. Same Stripe top-up flow.                         |
| Manual call from dashboard               | Conversation                  | Yes        | Business owner can trigger call from reservation detail page at any time.                      |
| Voice call logs per reservation          | Conversation                  | Yes        | `voice_call_logs` table with status, duration, cost, Twilio SID.                              |
| Inbound client calls                     | Conversation                  | No         | Out of scope — Twilio Voice is outbound only in this phase.                                    |
| Call recording                           | Conversation                  | No         | Out of scope — RGPD compliance not in scope for Phase 8.                                       |
| AI voice synthesis (non-Polly)           | Conversation                  | No         | Polly via Twilio is sufficient for MVP. ElevenLabs integration deferred.                       |

---

## Section 4 — Detailed Sprint Breakdown

### 4.11 Sprint 11 — Voice Core Backend (Weeks 19–20)

#### 4.11.1 Sprint Objectives

- `voice_call_logs` table and voice credit columns migrated
- `VoiceCallService` initiates Twilio Voice calls with French TwiML
- DTMF webhook controller handles `<Gather>` responses and updates reservation status
- Automatic trigger integrated into reservation flow (high-risk scoring)
- `PlaceVoiceCallJob` queued asynchronously with retry logic

---

#### 4.11.2 Database Migrations

| Migration name                              | Description                                                                                                                                                                                                                                                                                                                                                                          |
|---------------------------------------------|--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `create_voice_call_logs_table`              | id UUID PK, reservation_id UUID FK→reservations(id) ON DELETE CASCADE NOT NULL, business_id UUID FK→businesses(id) ON DELETE CASCADE NOT NULL, to_phone VARCHAR(20) NOT NULL, attempt_number SMALLINT NOT NULL DEFAULT 1, status ENUM('initiated','ringing','answered','confirmed','declined','no_answer','failed') NOT NULL DEFAULT 'initiated', dtmf_response CHAR(1) nullable, duration_seconds SMALLINT nullable, cost_cents SMALLINT nullable, twilio_call_sid VARCHAR(34) UNIQUE nullable, created_at TIMESTAMPTZ, updated_at TIMESTAMPTZ. Indexes: `reservation_id` btree, `business_id+created_at` composite btree, `twilio_call_sid` btree (webhook lookup), `status` btree. |
| `add_voice_credits_to_businesses_table`     | Add `voice_credit_cents INT NOT NULL DEFAULT 0`, `voice_monthly_cap_cents INT NOT NULL DEFAULT 0`, `voice_auto_renew BOOLEAN NOT NULL DEFAULT true`, `voice_last_renewed_at TIMESTAMPTZ nullable`, `voice_auto_call_enabled BOOLEAN NOT NULL DEFAULT false`, `voice_auto_call_score_threshold SMALLINT nullable` (call if score < threshold), `voice_auto_call_min_party_size SMALLINT nullable` (call if party_size ≥ value), `voice_retry_count SMALLINT NOT NULL DEFAULT 3`, `voice_retry_delay_minutes SMALLINT NOT NULL DEFAULT 10`. Indexes: `voice_auto_call_enabled` btree. |

---

#### 4.11.3 Back-end Tasks

| ID         | Task                                                                                                                                                                                                                                                                                                                                                                              | PRD Ref      |
|------------|-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|--------------|
| P8-BE-001  | Create migration `create_voice_call_logs_table` per Section 4.11.2.                                                                                                                                                                                                                                                                                                               | Conversation |
| P8-BE-002  | Create migration `add_voice_credits_to_businesses_table` per Section 4.11.2.                                                                                                                                                                                                                                                                                                      | Conversation |
| P8-BE-003  | Create `app/Models/VoiceCallLog.php` — `HasUuids`; `belongsTo(Reservation::class)`; `belongsTo(Business::class)`; cast `status` to `VoiceCallStatusEnum`; scope `forReservation(string $id): Builder`; scope `pending(): Builder` filters status IN (initiated, ringing).                                                                                                        | Conversation |
| P8-BE-004  | Create `app/Enums/VoiceCallStatusEnum.php` — cases: `Initiated`, `Ringing`, `Answered`, `Confirmed`, `Declined`, `NoAnswer`, `Failed`; `label(): string` French labels; `isTerminal(): bool` returns true for Confirmed/Declined/NoAnswer/Failed.                                                                                                                                 | Conversation |
| P8-BE-005  | Create `app/Services/VoiceCallService.php` — `initiateCall(Reservation $reservation, int $attemptNumber = 1): VoiceCallLog` creates `VoiceCallLog`, calls Twilio REST API `POST /2010-04-01/Accounts/{SID}/Calls.json` with `To=$reservation->customer->phone`, `From=config('services.twilio.voice_number')`, `Url={APP_URL}/webhooks/voice/twiml/{log_id}`, `StatusCallback={APP_URL}/webhooks/voice/status/{log_id}`, `StatusCallbackMethod=POST`; stores Twilio SID in log; deducts `voice_credit_cents` via `VoiceCreditService`; throws `VoiceInsufficientCreditException` if balance < cost; returns log.  | Conversation |
| P8-BE-006  | Create `app/Services/VoiceCreditService.php` — mirrors `LeoWhatsAppCreditService` exactly with `voice_credit_cents` field: `getBalance`, `hasSufficientCredit`, `deduct` (suspends `voice_auto_call_enabled=false` at 0, dispatches `VoiceCreditExhaustedEvent`), `topUp` (re-enables `voice_auto_call_enabled` if was suspended), `getCallCost(): int` returns `config('services.twilio.voice_cost_per_call_cents')` (default: 8 cents ≈ ~0,08€).  | Conversation |
| P8-BE-007  | Create `app/Http/Controllers/Webhook/VoiceTwimlController.php` — `twiml(VoiceCallLog $log): Response` returns TwiML XML: `<Response><Say voice="Polly.Léa" language="fr-FR">Bonjour {clientName}, vous avez une réservation chez {businessName} le {date} à {time} pour {partySize} personne(s). Appuyez sur 1 pour confirmer, sur 2 pour annuler.</Say><Gather numDigits="1" action="{APP_URL}/webhooks/voice/gather/{log->id}" timeout="10"><Say voice="Polly.Léa" language="fr-FR">Veuillez appuyer sur une touche.</Say></Gather><Say voice="Polly.Léa" language="fr-FR">Nous n'avons pas reçu votre réponse. Merci.</Say></Response>`; returns `Content-Type: text/xml`.  | Conversation |
| P8-BE-008  | Create `app/Http/Controllers/Webhook/VoiceGatherController.php` — `gather(Request $request, VoiceCallLog $log): Response` handles `<Gather>` callback from Twilio; reads `$request->input('Digits')`; if '1': updates log status=confirmed, updates reservation status to `confirmed` via existing `UpdateStatusRequest` flow, returns TwiML confirmation message; if '2': updates log status=declined, updates reservation status to `cancelled_by_client`, dispatches `NotifyWaitlistJob` if waitlist enabled, returns TwiML farewell; else: returns TwiML "Touche non reconnue" and re-asks.  | Conversation |
| P8-BE-009  | Create `app/Http/Controllers/Webhook/VoiceStatusController.php` — `status(Request $request, VoiceCallLog $log): Response` handles Twilio `StatusCallback`; reads `CallStatus` param; maps Twilio statuses (completed→answered, no-answer→no_answer, failed→failed, busy→no_answer) to `VoiceCallStatusEnum`; if no_answer and `attempt_number < business->voice_retry_count`: dispatches `PlaceVoiceCallJob` with delay `business->voice_retry_delay_minutes`; returns 200.  | Conversation |
| P8-BE-010  | Create `app/Jobs/PlaceVoiceCallJob.php` — `handle()`: receives `Reservation $reservation`, `int $attemptNumber`; validates reservation status is still `pending` (abort if already confirmed/cancelled); checks `VoiceCreditService::hasSufficientCredit()`; calls `VoiceCallService::initiateCall()`; handles `VoiceInsufficientCreditException` by logging and not retrying; no queue retry (Twilio callback handles retries); dispatched with delay for retry attempts.  | Conversation |
| P8-BE-011  | Update `app/Observers/ReservationObserver.php` — in `created()`: after existing SMS dispatch, check `business->voice_auto_call_enabled` AND (`customer->score < business->voice_auto_call_score_threshold` OR `reservation->party_size >= business->voice_auto_call_min_party_size`); if condition met AND credits available, dispatch `PlaceVoiceCallJob` with 60-second delay (allow SMS to be delivered first).  | Conversation |
| P8-BE-012  | Create `app/Http/Controllers/Api/VoiceCallController.php` — `initiate(Reservation $reservation): JsonResponse` allows manual call from dashboard; validates reservation belongs to authenticated business (ReservationPolicy); calls `PlaceVoiceCallJob@dispatchSync()` (immediate); returns `VoiceCallLogResource` 202; `logs(Reservation $reservation): JsonResponse` returns all `VoiceCallLog[]` for the reservation as `VoiceCallLogResource[]`.  | Conversation |
| P8-BE-013  | Create `app/Http/Resources/VoiceCallLogResource.php` — expose: id, reservation_id, attempt_number, status, dtmf_response, duration_seconds, cost_cents, created_at; never expose twilio_call_sid.  | Conversation |
| P8-BE-014  | Create `app/Events/VoiceCreditExhaustedEvent.php` + `app/Listeners/SendVoiceCreditExhaustedNotification.php` — mirrors Phase 6 pattern; sends `VoiceCreditExhaustedMail` to business owner; create corresponding Mailable.  | Conversation |
| P8-BE-015  | Add `config/services.php` voice entries: `twilio.voice_number` (from `TWILIO_VOICE_NUMBER` env), `twilio.voice_cost_per_call_cents` (from `TWILIO_VOICE_COST_CENTS` env, default 8); add to `.env.example`.  | Conversation |

---

#### 4.11.4 Back-end Tests (TDD)

| Test File                                                        | Test Cases                                                                                                                                                                                                                                                                 |
|------------------------------------------------------------------|----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `tests/Unit/Services/VoiceCallServiceTest.php`                  | initiateCall creates VoiceCallLog with status=initiated, initiateCall calls Twilio API with correct params, initiateCall deducts credit via VoiceCreditService, initiateCall throws VoiceInsufficientCreditException when balance < cost, initiateCall stores twilio_call_sid in log |
| `tests/Unit/Services/VoiceCreditServiceTest.php`                | getBalance returns voice_credit_cents, hasSufficientCredit true when balance >= cost, deduct decrements balance, deduct disables voice_auto_call_enabled at 0, deduct dispatches VoiceCreditExhaustedEvent, topUp re-enables voice_auto_call_enabled, getCallCost returns configured value |
| `tests/Unit/Jobs/PlaceVoiceCallJobTest.php`                     | aborts when reservation is already confirmed, aborts when reservation is already cancelled, calls VoiceCallService::initiateCall for pending reservation, catches VoiceInsufficientCreditException and logs without retrying |
| `tests/Feature/Voice/VoiceTwimlControllerTest.php`              | returns valid XML with correct business name and slot info, includes Gather verb with correct action URL, uses Polly.Léa voice with fr-FR language, returns 404 for unknown log id |
| `tests/Feature/Voice/VoiceGatherControllerTest.php`             | digit 1 confirms reservation and returns confirmation TwiML, digit 2 cancels reservation and returns farewell TwiML, digit 2 dispatches NotifyWaitlistJob when waitlist enabled, unrecognised digit returns re-ask TwiML |
| `tests/Feature/Voice/VoiceStatusControllerTest.php`             | no-answer with remaining retries dispatches PlaceVoiceCallJob with delay, no-answer at max retries marks log as no_answer without dispatching, completed status updates log to answered, failed status updates log to failed |
| `tests/Feature/Voice/VoiceCallControllerTest.php`               | initiate dispatches PlaceVoiceCallJob and returns 202, initiate returns 403 for other business's reservation, logs returns all VoiceCallLog for reservation ordered by created_at |
| `tests/Feature/Voice/ReservationObserverVoiceTest.php`          | dispatches PlaceVoiceCallJob when score below threshold and auto_call enabled, dispatches when party_size >= min_party_size, does not dispatch when auto_call_enabled=false, does not dispatch when credit insufficient |

---

#### 4.11.5 Front-end Tasks

| ID         | Task                                                                                                                                                                                                                                                               | PRD Ref      |
|------------|--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|--------------|
| P8-FE-001  | Create `src/api/voiceCalls.ts` — typed client: `initiateVoiceCall(reservationId: string): Promise<VoiceCallLog>`, `getVoiceCallLogs(reservationId: string): Promise<VoiceCallLog[]>`, `getVoiceCreditStatus(): Promise<VoiceCreditStatus>`, `initiateVoiceTopUp(amountCents: number): Promise<{checkout_url: string}>`, `setVoiceCap(capCents: number, autoRenew: boolean): Promise<VoiceCreditStatus>`; define `VoiceCallLog`, `VoiceCreditStatus` interfaces.  | Conversation |
| P8-FE-002  | Create `src/composables/useVoiceCredits.ts` — mirrors `useWhatsAppCredits` with voice fields: `status`, `loading`, `error`, `fetchStatus`, `topUp`, `setCap`; computed: `balanceFormatted`, `isLowBalance`.  | Conversation |
| P8-FE-003  | Create `src/components/voice/VoiceCreditCard.vue` — identical layout to `WhatsAppCreditCard.vue` with voice branding: phone icon, "Crédits Appels" label; balance progress bar; "Recharger" and "Modifier" actions; low-balance warning; Props: `status: VoiceCreditStatus`. | Conversation |
| P8-FE-004  | Create `src/components/voice/VoiceCallLogList.vue` — table of call attempts for a reservation: attempt number, status badge, duration, cost, timestamp; empty state "Aucun appel effectué"; Props: `logs: VoiceCallLog[]`.  | Conversation |
| P8-FE-005  | Create `src/components/voice/VoiceCallStatusBadge.vue` — pill badge per `VoiceCallStatusEnum`: initiated (grey), ringing (blue pulse), answered (teal), confirmed (green), declined (red), no_answer (orange), failed (dark red); Props: `status: VoiceCallStatus`.  | Conversation |
| P8-FE-006  | Update `src/pages/ReservationDetailPage.vue` — add "Appeler" button (shown when reservation is pending); on click, calls `initiateVoiceCall()` and shows toast "Appel en cours…"; add `VoiceCallLogList` section below reservation details (fetched on mount).  | Conversation |

---

#### 4.11.6 Front-end Tests

| Test File                                                              | Test Cases                                                                                                                                               |
|------------------------------------------------------------------------|----------------------------------------------------------------------------------------------------------------------------------------------------------|
| `src/components/voice/__tests__/VoiceCallStatusBadge.test.ts`         | renders correct label and colour for each status, applies pulse animation class for ringing status                                                       |
| `src/components/voice/__tests__/VoiceCallLogList.test.ts`             | renders all log entries with correct status badges, shows empty state when logs array is empty, displays duration in human-readable format               |

---

#### 4.11.7 DevOps / Infrastructure Tasks

| ID         | Task                                                                                                                                                                                             | PRD Ref      |
|------------|--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|--------------|
| P8-DO-001  | Add Twilio Voice webhook routes to `routes/api.php` — `GET /webhooks/voice/twiml/{log}` and `POST /webhooks/voice/gather/{log}` and `POST /webhooks/voice/status/{log}`; no auth; validate Twilio signature via `TwilioSignatureMiddleware` (reuse from Phase 1 SMS webhook). | Conversation |
| P8-DO-002  | Add `TWILIO_VOICE_NUMBER` and `TWILIO_VOICE_COST_CENTS` to `.env.example`; document that the Twilio number must have Voice capability enabled in Twilio Console.  | Conversation |

---

#### 4.11.8 Deliverables Checklist

- [ ] `voice_call_logs` and voice credit columns migrated
- [ ] `VoiceCallService` initiates Twilio calls with French Polly TwiML
- [ ] `VoiceGatherController` confirms/cancels reservation on DTMF response
- [ ] `VoiceStatusController` handles no-answer retries up to configured count
- [ ] `PlaceVoiceCallJob` auto-triggered on high-risk reservation creation
- [ ] Voice credit deduction and exhaustion suspension operational
- [ ] Manual call from `ReservationDetailPage` with live status feedback
- [ ] All Sprint 11 backend + frontend tests passing

---

### 4.12 Sprint 12 — Voice Billing, Settings & Dashboard (Weeks 21–22)

#### 4.12.1 Sprint Objectives

- Voice credit top-up via Stripe Checkout operational end-to-end
- Business owner configures auto-call rules (score threshold, party size, retry settings) from dashboard
- Voice credit card displayed in settings page alongside WhatsApp credit card
- Monthly auto-renewal scheduled for voice credits
- Full CI green, coverage ≥ 80%

---

#### 4.12.2 Database Migrations

No new migrations in Sprint 12.

---

#### 4.12.3 Back-end Tasks

| ID         | Task                                                                                                                                                                                                                                                                                         | PRD Ref      |
|------------|----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|--------------|
| P8-BE-020  | Create `app/Http/Controllers/Api/VoiceCreditController.php` — `status(): JsonResponse` returns `VoiceCreditResource`; `topup(TopUpVoiceRequest $request): JsonResponse` creates Stripe Checkout Session with metadata `{type: 'voice_credit', business_id}`; returns `{checkout_url}`; `setCap(SetVoiceCapRequest $request): JsonResponse` updates `voice_monthly_cap_cents` and `voice_auto_renew`.  | Conversation |
| P8-BE-021  | Create `app/Http/Requests/TopUpVoiceRequest.php` — validate: `amount_cents` required\|integer\|min:100\|max:10000; French messages.  | Conversation |
| P8-BE-022  | Create `app/Http/Requests/SetVoiceCapRequest.php` — validate: `monthly_cap_cents` required\|integer\|min:0\|max:10000, `auto_renew` required\|boolean; French messages.  | Conversation |
| P8-BE-023  | Create `app/Http/Resources/VoiceCreditResource.php` — expose: balance_cents, balance_euros, monthly_cap_cents, monthly_cap_euros, auto_renew, auto_call_enabled, auto_call_score_threshold, auto_call_min_party_size, retry_count, retry_delay_minutes.  | Conversation |
| P8-BE-024  | Update `app/Http/Controllers/Webhook/StripeWebhookController.php` — extend `checkout.session.completed` handler to also handle `type='voice_credit'` metadata: calls `VoiceCreditService::topUp($business, $amount)`.  | Conversation |
| P8-BE-025  | Create `app/Http/Controllers/Api/VoiceSettingsController.php` — `update(VoiceSettingsRequest $request): JsonResponse` updates `voice_auto_call_enabled`, `voice_auto_call_score_threshold`, `voice_auto_call_min_party_size`, `voice_retry_count`, `voice_retry_delay_minutes` on business; returns `VoiceCreditResource`. | Conversation |
| P8-BE-026  | Create `app/Http/Requests/VoiceSettingsRequest.php` — validate: `auto_call_enabled` boolean, `score_threshold` integer\|min:0\|max:100\|nullable, `min_party_size` integer\|min:2\|max:50\|nullable, `retry_count` integer\|min:0\|max:5, `retry_delay_minutes` integer\|min:5\|max:120; French messages; custom rule: at least one of score_threshold or min_party_size must be set when auto_call_enabled=true.  | Conversation |
| P8-BE-027  | Create `app/Jobs/RenewVoiceCreditJob.php` + `app/Console/Commands/RenewVoiceCredits.php` — `leo:renew-voice-credits`; mirrors `RenewWhatsAppCreditJob` with `voice_*` fields; idempotency via `voice_last_renewed_at`; register in `Kernel.php` to run `monthly()`.  | Conversation |
| P8-BE-028  | Create `app/Mail/VoiceCreditRenewedMail.php` + `VoiceCreditExhaustedMail.php` — mirrors Phase 6 WhatsApp mails; subject "✅ Crédits Appels Léo renouvelés" / "⚠️ Crédits Appels épuisés".  | Conversation |

---

#### 4.12.4 Back-end Tests (TDD)

| Test File                                                           | Test Cases                                                                                                                                                                            |
|---------------------------------------------------------------------|---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `tests/Feature/Voice/VoiceCreditControllerTest.php`                | status returns credit and settings for authenticated business, topup creates Stripe Checkout and returns checkout_url, topup validates min/max amount_cents, setCap updates cap and auto_renew |
| `tests/Feature/Voice/VoiceStripeWebhookTest.php`                   | checkout.session.completed with voice_credit metadata tops up balance, event with non-voice metadata is ignored                                                                        |
| `tests/Feature/Voice/VoiceSettingsControllerTest.php`              | update enables auto_call with score_threshold, update returns 422 when auto_call_enabled=true but no threshold or party_size set, update returns 422 for invalid retry_count          |
| `tests/Unit/Jobs/RenewVoiceCreditJobTest.php`                      | renews credits for eligible businesses, skips auto_renew=false, skips already renewed this month, sends VoiceCreditRenewedMail                                                       |

---

#### 4.12.5 Front-end Tasks

| ID         | Task                                                                                                                                                                                                                                                                | PRD Ref      |
|------------|---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|--------------|
| P8-FE-020  | Create `src/components/voice/VoiceSettingsCard.vue` — settings form: auto-call toggle; score threshold input (0–100, shown when toggle on); min party size input (2–50, shown when toggle on); retry count select (0, 1, 2, 3 attempts); retry delay select (5, 10, 15, 30 min); save button; info tooltip "Léo appellera automatiquement les clients à risque avant leur réservation."; Props: none (uses composable). | Conversation |
| P8-FE-021  | Create `src/views/VoiceView.vue` — page at `/voice` route; header "Appels automatiques"; shows `VoiceCreditCard` + `VoiceSettingsCard`; add route to `src/router/index.ts` with requiresAuth and sidebar nav link (phone icon).  | Conversation |
| P8-FE-022  | Create `src/views/VoiceReturnView.vue` + route `/voice/topup/return` — mirrors `WhatsAppReturnView.vue` for voice credit top-up Stripe redirect (polls balance until updated, shows success/cancel state).  | Conversation |
| P8-FE-023  | Update `src/api/voiceCalls.ts` — add `getVoiceSettings(): Promise<VoiceSettings>`, `updateVoiceSettings(payload): Promise<VoiceSettings>`; add `VoiceSettings` interface with all auto-call fields.  | Conversation |

---

#### 4.12.6 Front-end Tests

| Test File                                                             | Test Cases                                                                                                                                                     |
|-----------------------------------------------------------------------|----------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `src/components/voice/__tests__/VoiceCreditCard.test.ts`             | renders balance and cap, progress bar colour matches thresholds, Recharger button emits topup, shows low-balance warning                                       |
| `src/components/voice/__tests__/VoiceSettingsCard.test.ts`           | auto-call toggle shows/hides threshold and party size fields, save button calls updateVoiceSettings, shows validation error when toggle on but no criteria set |
| `src/views/__tests__/VoiceReturnView.test.ts`                        | shows success and polls balance on status=success, shows cancelled on status=cancel                                                                            |

---

#### 4.12.7 DevOps / Infrastructure Tasks

| ID         | Task                                                                                                                                         | PRD Ref      |
|------------|----------------------------------------------------------------------------------------------------------------------------------------------|--------------|
| P8-DO-020  | Add voice credit API routes to `routes/api.php` — `GET/POST/PATCH /api/v1/voice/credits`, `GET/PATCH /api/v1/voice/settings`; all protected by `auth:sanctum`. | Conversation |
| P8-DO-021  | Update GitHub Actions CI — add `TWILIO_VOICE_NUMBER=+33000000000`, `TWILIO_VOICE_COST_CENTS=8` to test env; ensure Voice webhook tests use `Http::fake()`. | Conversation |

---

#### 4.12.8 Deliverables Checklist

- [ ] Stripe top-up flow for voice credits end-to-end operational
- [ ] `VoiceSettingsCard` allows configuring auto-call rules from dashboard
- [ ] `VoiceView` page with credit card + settings card at `/voice`
- [ ] Monthly auto-renewal command registered and tested
- [ ] `VoiceReturnView` handles Stripe redirect correctly
- [ ] All Sprint 12 backend + frontend tests passing
- [ ] CI pipeline green, coverage ≥ 80%

---

## Section 5 — API Endpoints Delivered in Phase 8

| Method | Endpoint                             | Controller                          | Auth    | Notes                                                                                                         |
|--------|--------------------------------------|-------------------------------------|---------|---------------------------------------------------------------------------------------------------------------|
| GET    | `/webhooks/voice/twiml/{log}`        | `VoiceTwimlController@twiml`        | No      | Returns TwiML XML. Twilio must be able to reach this URL. Validates log exists.                               |
| POST   | `/webhooks/voice/gather/{log}`       | `VoiceGatherController@gather`      | No      | Twilio `<Gather>` callback. Body: `Digits` (1 or 2). Returns TwiML response.                                 |
| POST   | `/webhooks/voice/status/{log}`       | `VoiceStatusController@status`      | No      | Twilio `StatusCallback`. Body: `CallStatus`. Returns 200.                                                     |
| POST   | `/api/v1/reservations/{id}/call`     | `VoiceCallController@initiate`      | Bearer  | Initiates manual voice call. Returns `VoiceCallLogResource` 202.                                             |
| GET    | `/api/v1/reservations/{id}/calls`    | `VoiceCallController@logs`          | Bearer  | Returns `VoiceCallLogResource[]` for reservation.                                                            |
| GET    | `/api/v1/voice/credits`              | `VoiceCreditController@status`      | Bearer  | Returns `VoiceCreditResource`.                                                                                |
| POST   | `/api/v1/voice/credits/topup`        | `VoiceCreditController@topup`       | Bearer  | Body: `{amount_cents}`. Returns `{checkout_url}`.                                                            |
| PATCH  | `/api/v1/voice/credits/cap`          | `VoiceCreditController@setCap`      | Bearer  | Body: `{monthly_cap_cents, auto_renew}`. Returns `VoiceCreditResource`.                                      |
| GET    | `/api/v1/voice/settings`             | `VoiceSettingsController@show`      | Bearer  | Returns auto-call configuration fields.                                                                      |
| PATCH  | `/api/v1/voice/settings`             | `VoiceSettingsController@update`    | Bearer  | Body: auto-call fields. Returns updated `VoiceCreditResource`.                                               |

---

## Section 6 — Exit Criteria

| #  | Criterion                                                                                                  | Validated |
|----|------------------------------------------------------------------------------------------------------------|-----------|
| 1  | All P8 functional requirements implemented: voice call flow, DTMF, retries, credits, dashboard            | [ ]       |
| 2  | Backend test coverage ≥ 80%                                                                                | [ ]       |
| 3  | Frontend test coverage ≥ 80%                                                                               | [ ]       |
| 4  | Pint passes with zero errors                                                                               | [ ]       |
| 5  | PHPStan level 8 passes with zero errors                                                                    | [ ]       |
| 6  | ESLint + Prettier passes with zero errors                                                                  | [ ]       |
| 7  | All Pest tests pass                                                                                        | [ ]       |
| 8  | All Vitest tests pass                                                                                      | [ ]       |
| 9  | CI pipeline green on `main`                                                                                | [ ]       |
| 10 | Staging test: high-risk reservation created → Twilio call initiated → digit 1 pressed → reservation confirmed | [ ]   |
| 11 | Staging test: no-answer scenario → retries fire at correct interval → log shows no_answer after max retries | [ ]    |
| 12 | Staging test: Stripe top-up → `checkout.session.completed` → voice balance credited                       | [ ]       |
| 13 | `docs/dev/phase8.md` fully updated                                                                        | [ ]       |

---

## Section 7 — Risks Specific to Phase 8

| Risk                                                              | Probability | Impact | Mitigation                                                                                                              |
|-------------------------------------------------------------------|-------------|--------|-------------------------------------------------------------------------------------------------------------------------|
| Twilio Voice number not provisioned for outbound calls            | Medium      | High   | Provision and test Twilio number before Sprint 11 starts; use Twilio test credentials in CI.                           |
| French Polly.Léa voice not available on Twilio account tier       | Low         | Medium | Fallback to `alice` (standard Twilio French voice); document in config; test in staging before Sprint 11.             |
| Client phone number in wrong format (not E.164)                   | Medium      | Medium | Validate E.164 on reservation creation (already enforced in Phase 1); log and skip call if format invalid.            |
| RGPD: voice call without prior consent                            | Medium      | High   | Add `voice_call_consent` to reservation creation flow (checkbox or SMS opt-in confirmation); defer enforcement to Phase 9 polish. |
| Twilio TwiML URL unreachable from Twilio servers (staging tunnel) | Medium      | High   | Use ngrok or Cloudflare Tunnel for local dev; Staging must have a public HTTPS URL registered in Twilio Console.      |

---

## Section 8 — External Dependencies

| Service / Library       | Phase 8 Usage                                              | Fallback if Unavailable                                         |
|-------------------------|------------------------------------------------------------|------------------------------------------------------------------|
| Twilio Voice API        | Outbound calls, TwiML serving, DTMF gather, status webhook | Log call attempt; notify business owner to call client manually |
| AWS Polly (via Twilio)  | French female voice synthesis (Polly.Léa)                  | Twilio `<Say voice="alice">` French fallback                    |
| Stripe API              | Voice credit top-up Checkout Session + webhook             | Manual credit via Artisan `leo:manual-voice-topup {business} {cents}` |
| Laravel Scheduler       | `leo:renew-voice-credits` monthly                          | Manual Artisan invocation                                        |

---

## Assumptions

- A single Twilio number (from `TWILIO_VOICE_NUMBER`) is used for all outbound calls. Caller ID will show this number on client phones.
- Voice cost is approximated at a fixed rate per call attempt (`TWILIO_VOICE_COST_CENTS`, default 8 cents). Exact Twilio billing varies by country and duration; monthly Twilio invoices vs. platform credits should be reconciled manually.
- `Polly.Léa` is the AWS Polly Neural voice available via Twilio. If unavailable on the account, `alice` (Twilio standard French) is the fallback — documented in `config/services.php`.
- DTMF response timeout is 10 seconds. After timeout, the call ends and `VoiceStatusController` handles `CallStatus=completed` without a DTMF response, which is treated as no-answer.
- Voice auto-call trigger fires on reservation creation (in `ReservationObserver@created`), not on reminder schedule — this is intentional for same-day or next-day reservations.
