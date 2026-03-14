# Phase 6 — Léo WhatsApp Channel with Prepaid Credit System

| Field            | Value                                                                                          |
|------------------|-----------------------------------------------------------------------------------------------|
| **Phase**        | 6 of 6                                                                                         |
| **Name**         | Léo WhatsApp — Meta Cloud API with Prepaid Credit Wallet                                      |
| **Duration**     | Weeks 11–14 (4 weeks)                                                                          |
| **Milestone**    | M6 — At least 3 pilot businesses using Léo via WhatsApp with prepaid credits active           |
| **PRD Sections** | §Leo PRD (docs/leo_expression_de_besoins.md), §Leo Phase 5 channel abstraction               |
| **Prerequisite** | Phase 5 fully completed and validated (all exit criteria met, Léo Telegram live in production) |
| **Status**       | Not started                                                                                    |

---

## Section 1 — Phase Objectives

| ID        | Objective                                                                                                                        | Verifiable?                                         |
|-----------|----------------------------------------------------------------------------------------------------------------------------------|-----------------------------------------------------|
| P6-OBJ-1  | A business owner can activate WhatsApp as their Léo channel by setting a monthly budget and completing a Stripe top-up          | Feature test passes                                 |
| P6-OBJ-2  | Inbound WhatsApp messages from business owners are processed end-to-end via Meta Cloud API webhook → Gemini → response          | Feature test + integration test passes              |
| P6-OBJ-3  | Each WhatsApp conversation deducts credits from the prepaid wallet; when balance reaches 0, channel is suspended automatically  | Unit test passes                                    |
| P6-OBJ-4  | Business owner can top up their credit balance at any time via a Stripe Checkout one-time payment                               | Feature test passes                                 |
| P6-OBJ-5  | Monthly auto-renewal charges the configured monthly budget on the 1st of each month and adds credits to balance                 | Unit test + manual staging verification             |
| P6-OBJ-6  | Credits roll over month-to-month (unused credits never expire)                                                                  | Unit test passes                                    |
| P6-OBJ-7  | Business owner receives email notification when balance is exhausted and when monthly renewal succeeds                          | Feature test passes                                 |
| P6-OBJ-8  | Dashboard credit card shows balance, auto-renew toggle, cap setting, and top-up flow with Stripe redirect                       | E2E test passes                                     |
| P6-OBJ-9  | WhatsApp conversation windows (24h) are tracked to avoid double-charging for the same open session                              | Unit test passes                                    |
| P6-OBJ-10 | `LeoChannelInterface` implementation for WhatsApp passes all interface contract tests (same shape as TelegramChannel)           | Unit test passes                                    |
| P6-OBJ-11 | Backend test coverage ≥ 80%, frontend ≥ 80%                                                                                    | CI coverage gate passes                             |

---

## Section 2 — Entry Criteria

- Phase 5 exit criteria all validated (all items checked in phase5.md Section 6)
- Léo Telegram channel live in production and used by at least 1 pilot business
- `LeoChannelInterface` contract stable (no planned changes to the 3-method interface)
- Meta WhatsApp Business account created and verified with a dedicated phone number
- `WHATSAPP_PHONE_NUMBER_ID`, `WHATSAPP_ACCESS_TOKEN`, `WHATSAPP_VERIFY_TOKEN` available in `.env`
- Stripe account configured (Phase 3 prerequisite) — `checkout.session.completed` event added to webhook allowed events
- `CLAUDE.md` updated to reference Phase 6 tasks

---

## Section 3 — Scope — Requirement Traceability

| Requirement Group                    | Source Ref                    | Included?  | Notes                                                                                                              |
|--------------------------------------|-------------------------------|------------|--------------------------------------------------------------------------------------------------------------------|
| WhatsApp Business API integration    | Leo PRD §4, Phase 5 scope     | Yes        | Full — inbound + outbound via Meta Cloud API v20.0. One channel per business (existing UNIQUE constraint).        |
| Prepaid credit wallet                | Conversation 2026-03-14       | Yes        | Balance in cents on `businesses` table. Roll-over credits. Hard cap = current balance. Monthly auto-top-up.       |
| Stripe top-up (one-time payment)     | Conversation 2026-03-14       | Yes        | Stripe Checkout mode=payment. `checkout.session.completed` webhook credits the account.                           |
| Monthly auto-renewal                 | Conversation 2026-03-14       | Yes        | Scheduler: 1st of month, creates Stripe Invoice Item + finalises. Business sets monthly budget amount.            |
| WhatsApp 24h conversation windows    | Meta API pricing model        | Yes        | Tracked in `whatsapp_conversation_windows` table. No double-charge within open window.                            |
| Credit exhaustion → channel suspend  | Conversation 2026-03-14       | Yes        | `is_active=false` on `leo_channels` when `whatsapp_credit_cents` reaches 0. Reactivated on top-up.               |
| Low-balance email notification       | Conversation 2026-03-14       | Yes        | Email when balance drops below configurable threshold (default: 1€).                                              |
| Telegram channel unchanged           | Phase 5                       | Yes        | Telegram remains free, included in 9€/month Léo add-on. No changes to TelegramChannel.                           |
| WhatsApp pricing in LandingView      | Phase 5 P5-FE-031             | Yes        | Update Léo add-on card: mention WhatsApp prepaid credits as optional add-on.                                      |
| WhatsApp marketing conversations     | Meta API                      | No         | Out of scope — Léo is a service/utility assistant, not a marketing tool.                                          |
| SMS channel full delivery            | Phase 5 scope                 | No         | Deferred — TwilioSmsChannel stub remains. Full implementation is a future phase.                                  |
| WhatsApp multi-bot (one bot/business)| Conversation                  | No         | Single shared phone number for MVP. Multi-number support is a future enhancement.                                 |

---

## Section 4 — Detailed Sprint Breakdown

### 4.7 Sprint 7 — WhatsApp Core Backend (Weeks 11–12)

#### 4.7.1 Sprint Objectives

- `WhatsAppChannel` implementing `LeoChannelInterface` fully operational (webhook verify, parse inbound, send outbound)
- Meta webhook registered and processing inbound messages end-to-end in staging
- Prepaid credit schema deployed: `whatsapp_credit_cents`, `whatsapp_monthly_cap_cents`, conversation windows table
- Credit deduction and suspension logic operational: balance check before send, decrement after, suspend at 0
- Low-balance email notification dispatched automatically

---

#### 4.7.2 Database Migrations

| Migration name                                | Description                                                                                                                                                                                                                                                                                                                                                                                                         |
|-----------------------------------------------|---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `add_whatsapp_credits_to_businesses_table`    | Add `whatsapp_credit_cents INT NOT NULL DEFAULT 0` (current prepaid balance in euro-cents), `whatsapp_monthly_cap_cents INT NOT NULL DEFAULT 0` (monthly auto-top-up amount in cents; 0 = no WhatsApp budget set), `whatsapp_auto_renew BOOLEAN NOT NULL DEFAULT true` (charge cap on 1st of month), `whatsapp_last_renewed_at TIMESTAMPTZ nullable` (last auto-renewal timestamp for deduplication). Indexes: `whatsapp_credit_cents` (btree, for low-balance queries), `whatsapp_auto_renew` (btree).                |
| `create_whatsapp_conversation_windows_table`  | id UUID PK, channel_id UUID FK→leo_channels(id) ON DELETE CASCADE NOT NULL, contact_phone VARCHAR(50) NOT NULL (Meta sender phone, e.g. "33612345678"), conversation_type ENUM('service','utility') NOT NULL (`service` = user-initiated 24h window; `utility` = business-initiated notification), opened_at TIMESTAMPTZ NOT NULL, expires_at TIMESTAMPTZ NOT NULL (opened_at + 24 hours), cost_cents INT NOT NULL DEFAULT 0 (deducted amount for this window), created_at TIMESTAMPTZ. Indexes: `(channel_id, contact_phone, conversation_type, expires_at)` composite btree (lookup active window per contact), `expires_at` btree (cleanup job). No UNIQUE — multiple historical windows per contact are valid; only one active window is enforced at application level via `active()` scope. |

---

#### 4.7.3 Back-end Tasks

| ID         | Task                                                                                                                                                                                                                                                                                                                                                                                                               | PRD Ref              |
|------------|--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|----------------------|
| P6-BE-001  | Create `app/Leo/Channels/WhatsAppChannel.php` implementing `LeoChannelInterface` — `parseInbound(Request $request): LeoInboundMessage` extracts `entry[0].changes[0].value.messages[0].text.body` (text) and `messages[0].from` (sender phone) from Meta Cloud API webhook payload; returns null for non-message events (status updates, delivery receipts); `verifyWebhook(Request $request): bool` handles GET challenge: verifies `hub.mode === 'subscribe'` and `hub.verify_token === config('services.whatsapp.verify_token')`, returns `hub.challenge` as response body; `sendMessage(string $recipientId, string $text): void` POSTs to `https://graph.facebook.com/v20.0/{WHATSAPP_PHONE_NUMBER_ID}/messages` with Bearer `WHATSAPP_ACCESS_TOKEN`, body `{"messaging_product":"whatsapp","to":$recipientId,"type":"text","text":{"body":$text}}`; throws `LeoChannelException` on HTTP error ≥ 400.                    | Leo PRD §4, Phase 5  |
| P6-BE-002  | Create migration `add_whatsapp_credits_to_businesses_table` per Section 4.7.2 — implement `up()/down()` with Blueprint; no default data seeding needed (existing businesses start at 0 credits).                                                                                                                                                                                                                    | Conversation         |
| P6-BE-003  | Create migration `create_whatsapp_conversation_windows_table` per Section 4.7.2 — implement `up()/down()`.                                                                                                                                                                                                                                                                                                         | Conversation         |
| P6-BE-004  | Create `app/Models/WhatsAppConversationWindow.php` — `HasUuids`, `belongsTo(LeoChannel::class)`; cast `conversation_type` to `ConversationTypeEnum`; no `updated_at` (insert-only); scope `active(): Builder` filters `expires_at > now()`; scope `forContact(string $channelId, string $phone, string $type): Builder` filters by `channel_id`, `contact_phone`, `conversation_type`, and `expires_at > now()`. | Conversation         |
| P6-BE-005  | Create `app/Leo/Services/LeoWhatsAppConversationTracker.php` — `hasActiveWindow(string $channelId, string $contactPhone, string $type): bool` queries `whatsapp_conversation_windows` via `forContact()` scope; `openWindow(string $channelId, string $contactPhone, string $type, int $costCents): WhatsAppConversationWindow` inserts new window with `opened_at = now()`, `expires_at = now()->addHours(24)`, `cost_cents = $costCents`; `purgeExpired(): int` deletes all rows where `expires_at < now()`, returns count deleted.                                                                                                                                                                                    | Conversation         |
| P6-BE-006  | Create `app/Leo/Services/LeoWhatsAppCreditService.php` — `getBalance(Business $business): int` returns `whatsapp_credit_cents`; `hasSufficientCredit(Business $business, int $requiredCents): bool` compares balance to required; `deduct(Business $business, int $costCents): void` wraps in DB transaction: decrements `whatsapp_credit_cents` by `$costCents` (floor at 0, not negative), if resulting balance <= 0 calls `suspendWhatsAppChannel(Business $business)` and dispatches `LeoWhatsAppCreditExhaustedEvent`; if resulting balance < `config('leo.whatsapp.low_balance_threshold_cents')` dispatches `LeoWhatsAppLowBalanceEvent`; `topUp(Business $business, int $amountCents): void` increments `whatsapp_credit_cents`, if WhatsApp channel exists with `is_active=false` (reason: credit exhaustion) sets `is_active=true`; `suspendWhatsAppChannel(Business $business): void` sets `leo_channels.is_active=false` for business's WhatsApp channel; `getConversationCost(string $type): int` returns `config('leo.whatsapp.cost_service_cents')` or `config('leo.whatsapp.cost_utility_cents')`.                                                                                                     | Conversation         |
| P6-BE-007  | Add WhatsApp cost configuration to `config/leo.php` — add `'whatsapp' => ['cost_service_cents' => 5, 'cost_utility_cents' => 10, 'low_balance_threshold_cents' => 100]`; document each value with inline comment (5 cents ≈ Meta service conversation Europe; 10 cents ≈ utility conversation Europe; 1€ low-balance threshold). Values are overridable via env `LEO_WHATSAPP_COST_SERVICE_CENTS` etc.             | Conversation         |
| P6-BE-008  | Register `WhatsAppChannel` in `AppServiceProvider` — add `'whatsapp' => WhatsAppChannel::class` to the channel factory binding map (alongside existing `'telegram'` entry); add `config/services.php` keys: `services.whatsapp.phone_number_id`, `services.whatsapp.access_token`, `services.whatsapp.verify_token`.                                                                                              | Phase 5 P5-BE-005    |
| P6-BE-009  | Update `app/Http/Controllers/LeoWebhookController.php` — add `whatsapp(Request $request): Response` method: (GET) if `$request->query('hub_mode') === 'subscribe'` delegate to `WhatsAppChannel::verifyWebhook()` and return `hub_challenge` as plain text 200; (POST) resolve channel by `external_identifier` matching `entry[0].changes[0].value.metadata.phone_number_id`, parse inbound, check active window via `LeoWhatsAppConversationTracker::hasActiveWindow()` for 'service' type, if no window check credit via `hasSufficientCredit()`, if insufficient credit send "Votre crédit Léo WhatsApp est épuisé. Rechargez depuis votre tableau de bord." and return 200, else open window + deduct credit, call `LeoGeminiService::ask()`, send response via `sendMessage()` (deduct 'utility' cost if outbound is business-initiated notification), log to `leo_message_logs`; always return 200.                 | Leo PRD §7           |
| P6-BE-010  | Create `app/Events/LeoWhatsAppCreditExhaustedEvent.php` — readonly class with `public Business $business, public LeoChannel $channel`; implements `ShouldQueue`; create `app/Events/LeoWhatsAppLowBalanceEvent.php` — same shape with `public int $balanceCents`.                                                                                                                                                  | Conversation         |
| P6-BE-011  | Create `app/Listeners/SendCreditExhaustedNotification.php` — listens to `LeoWhatsAppCreditExhaustedEvent`; sends `WhatsAppCreditExhaustedMail` to `business->owner->email` (or `business->email`); register in `EventServiceProvider`; create `app/Listeners/SendLowBalanceNotification.php` — listens to `LeoWhatsAppLowBalanceEvent`; sends `WhatsAppLowBalanceMail`; register in `EventServiceProvider`.         | Conversation         |
| P6-BE-012  | Create `app/Mail/WhatsAppCreditExhaustedMail.php` — Mailable with `Business $business`; subject "⚠️ Crédit Léo WhatsApp épuisé"; body (Blade view): business name, current balance (0€), CTA "Recharger maintenant" linking to `/leo`; create `app/Mail/WhatsAppLowBalanceMail.php` — Mailable; subject "Léo WhatsApp — crédit faible"; body: current balance in €, monthly cap, CTA to recharge; both extend default Laravel mail layout.                                                                                                                                                                                                                                                                                                               | Conversation         |
| P6-BE-013  | Create `app/Console/Commands/PurgeWhatsAppConversationWindows.php` — signature `whatsapp:purge-windows`; calls `LeoWhatsAppConversationTracker::purgeExpired()`; logs count via `Log::info()`; register in `app/Console/Kernel.php` to run `daily()`; add to DevOps docs.                                                                                                                                           | Conversation         |

---

#### 4.7.4 Back-end Tests (TDD)

| Test File                                                              | Test Cases                                                                                                                                                                                                                                                                                                              |
|------------------------------------------------------------------------|-------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `tests/Unit/Leo/WhatsAppChannelTest.php`                              | parses valid Meta webhook payload (extracts sender phone and message text), returns null for non-message payload (delivery status update), verifyWebhook returns true for valid verify_token on GET, verifyWebhook returns false for wrong verify_token, sendMessage dispatches correct HTTP POST to Meta Cloud API with Bearer token, sendMessage throws LeoChannelException on 4xx HTTP response, sendMessage throws LeoChannelException on network timeout |
| `tests/Unit/Leo/LeoWhatsAppConversationTrackerTest.php`               | hasActiveWindow returns false when no window exists, hasActiveWindow returns true when active window exists (expires_at in future), hasActiveWindow returns false when window is expired (expires_at in past), openWindow creates record with correct expires_at (+24h from now), openWindow stores cost_cents correctly, purgeExpired deletes all expired windows, purgeExpired preserves active windows, purgeExpired returns correct deleted count |
| `tests/Unit/Leo/LeoWhatsAppCreditServiceTest.php`                     | getBalance returns whatsapp_credit_cents value, hasSufficientCredit returns true when balance >= required, hasSufficientCredit returns false when balance < required, deduct decrements balance by cost, deduct floors balance at 0 (no negative balance), deduct sets WhatsApp channel is_active=false when balance reaches 0, deduct dispatches LeoWhatsAppCreditExhaustedEvent when balance exhausted, deduct dispatches LeoWhatsAppLowBalanceEvent when balance drops below threshold, topUp increments balance correctly, topUp reactivates suspended WhatsApp channel (is_active=false → true), topUp does not touch channel if already active, getConversationCost returns configured service cost, getConversationCost returns configured utility cost |
| `tests/Feature/Leo/WhatsAppWebhookTest.php`                           | GET returns hub_challenge plain text when verify_token matches, GET returns 403 when verify_token mismatches, POST 200 resolves business from phone_number_id and responds via Gemini, POST 200 with credit-exhausted message when whatsapp_credit_cents = 0, POST 200 logs inbound + outbound to leo_message_logs, POST does not charge service conversation cost when active window exists for sender, POST charges service conversation cost and opens window when no active window exists, POST 200 for unknown sender responds with "Numéro non reconnu", no phone number appears in any log or response |
| `tests/Feature/Leo/WhatsAppWebhookCreditIntegrationTest.php`          | full flow: active window + sufficient credit → message processed, full flow: no window + sufficient credit → window opened + credit deducted + message processed, full flow: no window + insufficient credit (0 balance) → channel suspended + credit-exhausted message sent + no Gemini call made |

---

#### 4.7.5 Front-end Tasks

| ID         | Task                                                                                                                                                                                                                                                                                                                    | PRD Ref      |
|------------|-------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|--------------|
| P6-FE-001  | Enable WhatsApp radio option in `src/components/leo/CreateLeoChannelForm.vue` — remove "Bientôt disponible" disabled state for WhatsApp; when WhatsApp is selected, show inline budget section: label "Budget mensuel WhatsApp", integer input (min: 1, max: 100, unit: €), auto-renew checkbox (default: true), info text "Ce montant sera prélevé immédiatement puis chaque 1er du mois. Les crédits non utilisés sont reportés."; on submit, first call `setMonthlyCap()` then `createChannel()`; disable submit if WhatsApp selected and budget = 0. | Conversation |
| P6-FE-002  | Create `src/api/whatsappCredits.ts` — typed API client: `getWhatsAppCreditStatus(businessId: string): Promise<WhatsAppCreditStatus>`, `initiateTopUp(amountCents: number): Promise<{checkout_url: string}>`, `setMonthlyCap(capCents: number, autoRenew: boolean): Promise<WhatsAppCreditStatus>`; define `WhatsAppCreditStatus` interface: `{ balance_cents: number, monthly_cap_cents: number, auto_renew: boolean, is_channel_active: boolean, low_balance_warning: boolean }`; all functions use existing `axios` instance. | Conversation |
| P6-FE-003  | Create `src/composables/useWhatsAppCredits.ts` — Composition API: `status: Ref<WhatsAppCreditStatus \| null>`, `loading: Ref<boolean>`, `error: Ref<string \| null>`, `fetchStatus()`, `topUp(amountCents: number)` (redirects to Stripe `checkout_url`), `setCap(cents: number, autoRenew: boolean)`; computed: `balanceFormatted` (e.g. "6,43 €"), `capFormatted`, `isLowBalance` (status.low_balance_warning), `balancePercent` (balance / cap * 100, capped 0–100). | Conversation |
| P6-FE-004  | Create `src/components/leo/WhatsAppCreditCard.vue` — displays: balance as segmented progress bar (`balancePercent`), color class `text-green-600` > 50%, `text-yellow-500` 20–50%, `text-red-600` < 20%; shows formatted balance and cap ("6,43 € / 10 €"); low-balance warning banner (red background, ⚠ icon) when `isLowBalance`; "Recharger" primary button emits `topup`; "Modifier le budget" link emits `edit-cap`; auto-renew status ("Renouvellement automatique le 1er du mois" or "Renouvellement manuel"); ARIA: progressbar role with aria-valuenow/min/max; Props: `status: WhatsAppCreditStatus`; Emits: `topup`, `edit-cap`. | Conversation |
| P6-FE-005  | Create `src/components/leo/WhatsAppTopUpModal.vue` — modal dialog: preset amount buttons [2€, 5€, 10€, 20€] (radio group), custom amount input below (number, min 1, max 100); summary line "Vous allez recharger X € via Stripe"; primary CTA "Recharger via Stripe" calls `initiateTopUp(amountCents)` → redirect to `checkout_url`; secondary "Annuler" closes modal; shows spinner during API call; disables CTA when amount = 0; ARIA: dialog role, aria-labelledby; Props: `modelValue: boolean` (v-model); Emits: `update:modelValue`. | Conversation |
| P6-FE-006  | Update `src/views/LeoView.vue` — after rendering `LeoChannelCard`, check `channel.channel === 'whatsapp'`: if true, mount `WhatsAppCreditCard` below with `useWhatsAppCredits` status; handle `@topup` event by opening `WhatsAppTopUpModal`; handle `@edit-cap` event by opening cap edit inline form (budget input + auto-renew toggle + save button); fetch credit status on component mount only when channel type is whatsapp. | Conversation |

---

#### 4.7.6 Front-end Tests

| Test File                                                              | Test Cases                                                                                                                                                                                                                                                             |
|------------------------------------------------------------------------|------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `src/composables/__tests__/useWhatsAppCredits.test.ts`                | fetchStatus populates status on success, fetchStatus sets loading true then false, fetchStatus sets error on API failure, balanceFormatted formats cents to locale string, isLowBalance computed true when low_balance_warning true, topUp calls initiateTopUp and redirects to checkout_url, setCap calls setMonthlyCap API with correct params |
| `src/components/leo/__tests__/WhatsAppCreditCard.test.ts`             | renders balance and cap formatted, progress bar is green when balance > 50%, progress bar is yellow when balance 20–50%, progress bar is red when balance < 20%, low-balance banner visible when isLowBalance=true, low-balance banner hidden when isLowBalance=false, Recharger button emits topup, ARIA progressbar attributes set correctly |
| `src/components/leo/__tests__/WhatsAppTopUpModal.test.ts`             | renders preset buttons 2€ 5€ 10€ 20€, selecting preset updates summary line, custom input updates summary line, submitting calls initiateTopUp with correct cents value, shows spinner during loading, disables CTA when amount is 0, cancel button emits update:modelValue false |

---

#### 4.7.7 DevOps / Infrastructure Tasks

| ID         | Task                                                                                                                                                                             | PRD Ref      |
|------------|----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|--------------|
| P6-DO-001  | Add `GET /webhooks/leo/whatsapp` and `POST /webhooks/leo/whatsapp` routes to `routes/api.php` — no `auth` middleware; GET handled by `LeoWebhookController@whatsappVerify`, POST by `LeoWebhookController@whatsapp`; add Meta IP ranges to `config/leo.php` allowlist (Meta publishes these at `graph.facebook.com`). | Conversation |
| P6-DO-002  | Add `WHATSAPP_PHONE_NUMBER_ID`, `WHATSAPP_ACCESS_TOKEN`, `WHATSAPP_VERIFY_TOKEN` to `.env.example` with placeholder comments explaining how to obtain each from Meta Business Manager; add corresponding keys to `config/services.php` under `whatsapp` key; add `LEO_WHATSAPP_COST_SERVICE_CENTS`, `LEO_WHATSAPP_COST_UTILITY_CENTS`, `LEO_WHATSAPP_LOW_BALANCE_THRESHOLD_CENTS` to `.env.example` with defaults.| Conversation |

---

#### 4.7.8 Deliverables Checklist

- [ ] `whatsapp_credit_cents`, `whatsapp_monthly_cap_cents`, `whatsapp_auto_renew` columns migrated on `businesses`
- [ ] `whatsapp_conversation_windows` table migrated and verified
- [ ] `WhatsAppChannel` implements all 3 interface methods with passing unit tests
- [ ] Meta webhook verification (GET challenge) responds correctly
- [ ] Inbound WhatsApp messages processed end-to-end (parse → Gemini → sendMessage)
- [ ] Credit deduction per conversation type operational
- [ ] Channel suspended automatically when balance reaches 0
- [ ] Credit-exhausted message sent to user when balance = 0
- [ ] Low-balance email notification dispatched
- [ ] `WhatsAppCreditCard.vue` renders balance, progress bar, and top-up button
- [ ] `WhatsAppTopUpModal.vue` functional with preset amounts
- [ ] All Sprint 7 backend unit + feature tests passing

---

### 4.8 Sprint 8 — Credit Billing, Dashboard & Polish (Weeks 13–14)

#### 4.8.1 Sprint Objectives

- Stripe top-up flow operational end-to-end (Checkout → webhook → credit added)
- Monthly auto-renewal Artisan command deployed and scheduled
- `WhatsAppReturnView` handles Stripe redirect with balance refresh
- Landing page updated with WhatsApp credit model explained clearly
- Full CI green with fake WhatsApp env vars

---

#### 4.8.2 Database Migrations

No additional migrations in Sprint 8. All schema changes delivered in Sprint 7.

---

#### 4.8.3 Back-end Tasks

| ID         | Task                                                                                                                                                                                                                                                                                                                                                                                                                | PRD Ref      |
|------------|----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|--------------|
| P6-BE-020  | Create `app/Http/Controllers/LeoWhatsAppCreditController.php` — protected by `auth:sanctum`: `status(Request $request): JsonResponse` returns `LeoWhatsAppCreditResource` for the authenticated business; `topup(TopUpWhatsAppRequest $request): JsonResponse` creates Stripe Checkout Session (`mode='payment'`, `line_items` with amount from request, `success_url` = `{FRONTEND_URL}/leo/whatsapp/topup/return?status=success&session_id={CHECKOUT_SESSION_ID}`, `cancel_url` = `{FRONTEND_URL}/leo/whatsapp/topup/return?status=cancel`), stores `metadata: ['type' => 'whatsapp_credit', 'business_id' => business->id]`; returns `{checkout_url: string}`; `setCap(SetWhatsAppCapRequest $request): JsonResponse` updates `whatsapp_monthly_cap_cents` and `whatsapp_auto_renew` on business; returns updated `LeoWhatsAppCreditResource`. | Conversation |
| P6-BE-021  | Create `app/Http/Requests/TopUpWhatsAppRequest.php` — validate: `amount_cents` required\|integer\|min:100\|max:10000 (1€ min, 100€ max per top-up); French error messages; custom message for min: "Le montant minimum est de 1 €.", max: "Le montant maximum par rechargement est de 100 €."                                                                                                                       | Conversation |
| P6-BE-022  | Create `app/Http/Requests/SetWhatsAppCapRequest.php` — validate: `monthly_cap_cents` required\|integer\|min:0\|max:10000 (100€ max/month), `auto_renew` required\|boolean; French error messages.                                                                                                                                                                                                                  | Conversation |
| P6-BE-023  | Create `app/Http/Resources/LeoWhatsAppCreditResource.php` — expose: `balance_cents`, `balance_euros` (rounded to 2 decimals), `monthly_cap_cents`, `monthly_cap_euros`, `auto_renew`, `is_channel_active` (reads `business->leoChannel->is_active` when channel type is whatsapp), `low_balance_warning` (balance_cents < config threshold).                                                                       | Conversation |
| P6-BE-024  | Update `app/Http/Controllers/StripeWebhookController.php` (Phase 3) — add handler for `checkout.session.completed`: if `$event->data->object->metadata->type === 'whatsapp_credit'`, retrieve business by `metadata->business_id`, call `LeoWhatsAppCreditService::topUp($business, $event->data->object->amount_total)`, log to `Log::info()` with business_id and amount; if metadata type is not `whatsapp_credit`, ignore silently (existing handlers unaffected). | Conversation |
| P6-BE-025  | Create `app/Jobs/RenewWhatsAppCreditJob.php` — `handle()`: receives `Business $business`; validates `whatsapp_auto_renew=true` and `whatsapp_monthly_cap_cents > 0` and `whatsapp_last_renewed_at` is not in current month (idempotency guard); creates Stripe Invoice Item on `business->stripe_customer_id` with amount = `whatsapp_monthly_cap_cents`, description "Léo WhatsApp — Crédit mensuel"; finalises and pays invoice immediately via `Stripe\Invoice::finalizeInvoice()->pay()`; calls `LeoWhatsAppCreditService::topUp($business, capCents)`; updates `whatsapp_last_renewed_at = now()`; sends `WhatsAppCreditRenewedMail`; retries 3× on Stripe API error with exponential backoff. | Conversation |
| P6-BE-026  | Create `app/Console/Commands/RenewWhatsAppCredits.php` — signature `leo:renew-whatsapp-credits`; queries businesses with `whatsapp_auto_renew=true AND whatsapp_monthly_cap_cents > 0` and `whatsapp_last_renewed_at IS NULL OR whatsapp_last_renewed_at < start of current month`; dispatches `RenewWhatsAppCreditJob` for each; outputs count dispatched; register in `app/Console/Kernel.php` to run `monthly()` (1st of each month at 06:00 UTC). | Conversation |
| P6-BE-027  | Create `app/Mail/WhatsAppCreditRenewedMail.php` — Mailable with `Business $business`, `int $amountCents`, `int $newBalanceCents`; subject "✅ Crédit Léo WhatsApp renouvelé"; body: recharged amount in €, new total balance in €, next renewal date (1st of next month), CTA "Voir mon crédit" linking to `/leo`; uses default Laravel mail layout. | Conversation |
| P6-BE-028  | Create Artisan command `app/Console/Commands/SetupWhatsAppWebhook.php` — signature `leo:setup-whatsapp-webhook`; calls Meta Graph API `POST /{WHATSAPP_PHONE_NUMBER_ID}/subscribed_apps` with Bearer token to subscribe; also calls `POST /app/subscriptions` to register webhook URL `{APP_URL}/webhooks/leo/whatsapp` with fields `messages`; outputs confirmation JSON from Meta; document in Audit Notes section of `docs/dev/phase6.md`. | Conversation |
| P6-BE-029  | Update `app/Http/Controllers/LeoChannelController.php` `store()` method — when `$request->channel === 'whatsapp'`, validate that `auth()->user()->business->whatsapp_monthly_cap_cents > 0` before creating channel; if cap is 0, return 422 JSON `{"message": "Veuillez définir un budget mensuel WhatsApp avant de créer ce canal."}`. This ensures no WhatsApp channel exists without credits configured. | Conversation |

---

#### 4.8.4 Back-end Tests (TDD)

| Test File                                                              | Test Cases                                                                                                                                                                                                                                                                               |
|------------------------------------------------------------------------|------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `tests/Feature/Leo/LeoWhatsAppCreditControllerTest.php`               | status returns balance and cap for authenticated business, status returns 401 for unauthenticated, topup creates Stripe Checkout Session and returns checkout_url, topup returns 422 when amount_cents < 100, topup returns 422 when amount_cents > 10000, setCap updates monthly_cap_cents and auto_renew, setCap returns 422 when cap > 10000 cents, setCap returns 401 for unauthenticated |
| `tests/Feature/Leo/WhatsAppCreditStripeWebhookTest.php`               | checkout.session.completed with whatsapp_credit metadata tops up balance by correct amount, checkout.session.completed with whatsapp_credit reactivates suspended WhatsApp channel, checkout.session.completed with non-whatsapp metadata is ignored, missing business_id metadata logs error and returns 200 without crashing |
| `tests/Unit/Leo/RenewWhatsAppCreditJobTest.php`                       | handle creates Stripe Invoice Item for correct cap amount, handle calls topUp with correct cents, handle updates whatsapp_last_renewed_at to now, handle skips business already renewed this month (idempotency), handle sends WhatsAppCreditRenewedMail, handle retries on Stripe exception |
| `tests/Unit/Leo/RenewWhatsAppCreditsCommandTest.php`                  | dispatches RenewWhatsAppCreditJob for eligible businesses, skips businesses with auto_renew=false, skips businesses with cap=0, skips businesses already renewed this month, outputs correct dispatched count |
| `tests/Unit/Leo/PurgeWhatsAppConversationWindowsCommandTest.php`      | deletes expired windows via purgeExpired, preserves active windows, logs deleted count, outputs count in console |
| `tests/Feature/Leo/LeoChannelControllerWhatsAppTest.php`              | store returns 422 when whatsapp selected but monthly_cap_cents = 0, store creates WhatsApp channel when cap > 0, store returns 409 when WhatsApp channel already exists for business |

---

#### 4.8.5 Front-end Tasks

| ID         | Task                                                                                                                                                                                                                                                                                                                    | PRD Ref      |
|------------|-------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|--------------|
| P6-FE-020  | Create `src/views/WhatsAppReturnView.vue` — public route, no auth required; reads `?status=success\|cancel` and `?session_id` from query params; on `status=success`: call `getWhatsAppCreditStatus()` with polling (max 10 attempts, 2s interval) until balance > previous value or timeout; show "Rechargement réussi ✅" with new balance once confirmed; on `status=cancel`: show "Rechargement annulé" with neutral icon; both states show "Retour à Léo" button navigating to `/leo`; shows spinner while polling. | Conversation |
| P6-FE-021  | Add `/leo/whatsapp/topup/return` route to `src/router/index.ts` — `requiresAuth: false` (Stripe redirects may not preserve session cookie), lazy-loaded `WhatsAppReturnView`; no sidebar nav link. | Conversation |
| P6-FE-022  | Update Léo add-on card in `src/views/LandingView.vue` Pricing section — update description: change subtitle to "Votre assistant Telegram + WhatsApp"; add bullet "Canal Telegram inclus — gratuit"; add bullet "Canal WhatsApp — crédits prépayés (à partir de 1€/mois)"; add info tooltip on the WhatsApp bullet: "Les coûts Meta WhatsApp sont refacturés via un portefeuille prépayé. Aucune mauvaise surprise : votre solde détermine votre limite.". | Conversation |
| P6-FE-023  | Create `src/components/leo/WhatsAppCapEditForm.vue` — inline form (not a modal): budget input (number, min 1, max 100, unit €), auto-renew toggle, save button "Enregistrer", cancel link; emits `saved` with new cap/auto-renew values, emits `cancelled`; shows validation error when budget < 1 or > 100; uses `useWhatsAppCredits().setCap()`; displays in `LeoView` when user clicks "Modifier le budget" on `WhatsAppCreditCard`. | Conversation |

---

#### 4.8.6 Front-end Tests

| Test File                                                          | Test Cases                                                                                                                                                                                          |
|--------------------------------------------------------------------|-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `src/views/__tests__/WhatsAppReturnView.test.ts`                  | shows success message when status=success, polls credit status until balance updated, shows spinner during polling, shows cancelled message when status=cancel, shows return button in both states, navigates to /leo on button click |
| `src/components/leo/__tests__/WhatsAppCapEditForm.test.ts`        | renders budget input and auto-renew toggle, shows validation error when budget < 1, shows validation error when budget > 100, save button calls setCap with correct cents, cancel emits cancelled, emits saved on success |

---

#### 4.8.7 DevOps / Infrastructure Tasks

| ID         | Task                                                                                                                                                                                                                                   | PRD Ref      |
|------------|----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|--------------|
| P6-DO-020  | Update GitHub Actions CI workflow — add `WHATSAPP_PHONE_NUMBER_ID=fake_number_id`, `WHATSAPP_ACCESS_TOKEN=fake_token`, `WHATSAPP_VERIFY_TOKEN=fake_verify_token` to test environment vars; add `LEO_WHATSAPP_COST_SERVICE_CENTS=5`, `LEO_WHATSAPP_COST_UTILITY_CENTS=10` defaults; ensure all WhatsApp feature tests run via `Http::fake()` without real API calls. | Conversation |
| P6-DO-021  | Add `checkout.session.completed` to Stripe webhook allowed events in Stripe Dashboard (document in Audit Notes — this requires a manual step in the Stripe UI under Developers → Webhooks → Edit endpoint → select events). Document the exact event names required in `docs/dev/phase6.md` Audit Notes. | Conversation |
| P6-DO-022  | Document Meta WhatsApp Business setup steps in `docs/dev/phase6.md` Audit Notes: (1) create Meta Business account, (2) add WhatsApp product to Meta app, (3) get a permanent access token via System User, (4) note the Phone Number ID, (5) run `php artisan leo:setup-whatsapp-webhook` to register the webhook. | Conversation |

---

#### 4.8.8 Deliverables Checklist

- [ ] `LeoWhatsAppCreditController` CRUD endpoints operational with auth guard
- [ ] Stripe Checkout top-up flow end-to-end: payment → webhook → balance credited
- [ ] `RenewWhatsAppCreditJob` dispatched monthly, idempotent, tested
- [ ] `leo:renew-whatsapp-credits` command registered in scheduler
- [ ] `leo:setup-whatsapp-webhook` command operational
- [ ] `WhatsAppReturnView` handles Stripe redirect and polls balance
- [ ] Landing page Léo card updated with WhatsApp prepaid mention
- [ ] `WhatsAppCapEditForm` allows changing monthly budget inline
- [ ] All Sprint 8 backend + frontend tests passing
- [ ] CI pipeline green with fake WhatsApp env vars

---

## Section 5 — API Endpoints Delivered in Phase 6

| Method | Endpoint                                    | Controller                                 | Auth    | Notes                                                                                                                              |
|--------|---------------------------------------------|--------------------------------------------|---------|------------------------------------------------------------------------------------------------------------------------------------|
| GET    | `/webhooks/leo/whatsapp`                    | `LeoWebhookController@whatsappVerify`      | No      | Meta webhook verification challenge. Returns `hub_challenge` plain text when `hub_verify_token` matches config.                    |
| POST   | `/webhooks/leo/whatsapp`                    | `LeoWebhookController@whatsapp`            | No      | Meta Cloud API inbound webhook. Always returns 200. Processes messages, checks credits, deducts balance, calls Gemini, responds.   |
| GET    | `/api/v1/leo/whatsapp/credits`              | `LeoWhatsAppCreditController@status`       | Bearer  | Returns `LeoWhatsAppCreditResource` for authenticated business: balance_euros, monthly_cap_euros, auto_renew, is_channel_active.   |
| POST   | `/api/v1/leo/whatsapp/credits/topup`        | `LeoWhatsAppCreditController@topup`        | Bearer  | Body: `{amount_cents: int}` (100–10000). Creates Stripe Checkout Session. Returns `{checkout_url: string}`. Redirect to Stripe.   |
| PATCH  | `/api/v1/leo/whatsapp/credits/cap`          | `LeoWhatsAppCreditController@setCap`       | Bearer  | Body: `{monthly_cap_cents: int, auto_renew: bool}`. Updates business budget settings. Returns updated `LeoWhatsAppCreditResource`. |

---

## Section 6 — Exit Criteria

| #  | Criterion                                                                                                     | Validated |
|----|---------------------------------------------------------------------------------------------------------------|-----------|
| 1  | All P6 functional requirements implemented: WhatsApp channel CRUD, inbound processing, credit wallet, billing | [ ]       |
| 2  | Backend test coverage ≥ 80% (measured via Pest + PCOV)                                                        | [ ]       |
| 3  | Frontend test coverage ≥ 80% (measured via Vitest)                                                            | [ ]       |
| 4  | Pint (code style) passes with zero errors                                                                      | [ ]       |
| 5  | PHPStan level 8 passes with zero errors                                                                        | [ ]       |
| 6  | ESLint + Prettier check passes with zero errors                                                                | [ ]       |
| 7  | All Pest tests pass (backend + feature + unit)                                                                 | [ ]       |
| 8  | All Vitest tests pass (frontend)                                                                               | [ ]       |
| 9  | CI pipeline green on `main` (both backend and frontend jobs)                                                   | [ ]       |
| 10 | Meta webhook processes a real inbound WhatsApp message end-to-end in staging (manual verification)            | [ ]       |
| 11 | Stripe top-up flow: payment → `checkout.session.completed` webhook → balance credited in staging              | [ ]       |
| 12 | Monthly auto-renewal tested in staging by manually running `leo:renew-whatsapp-credits` with test data        | [ ]       |
| 13 | Credit exhaustion tested in staging: balance depleted → channel suspended → top-up → channel reactivated      | [ ]       |
| 14 | Phone numbers never appear in any Léo WhatsApp message log or outbound message (security audit)               | [ ]       |
| 15 | `docs/dev/phase6.md` fully updated with all tasks marked `done` or `merged`                                   | [ ]       |

---

## Section 7 — Risks Specific to Phase 6

| Risk                                                           | Probability | Impact | Mitigation                                                                                                                                      |
|----------------------------------------------------------------|-------------|--------|-------------------------------------------------------------------------------------------------------------------------------------------------|
| Meta WhatsApp Business Account verification delays             | High        | High   | Start Meta account creation and phone number verification 2 weeks before Sprint 7; use Meta's test number for development (no verification needed). |
| Meta access token expiration (user tokens expire in 60 days)  | High        | High   | Use System User token from Meta Business Manager (does not expire); document rotation procedure in Audit Notes.                                  |
| Stripe `checkout.session.completed` event not added to webhook | Medium      | High   | Add to pre-Sprint-8 checklist; test with Stripe CLI `stripe listen --forward-to` in local dev.                                                  |
| WhatsApp conversation window cost approximation drift          | Medium      | Low    | Costs in `config/leo.php` are configurable; monitor actual Meta billing monthly; adjust via env var with no code change.                         |
| Monthly renewal double-charge (scheduler fires twice)         | Low         | High   | `whatsapp_last_renewed_at` idempotency guard in `RenewWhatsAppCreditJob` prevents double charge regardless of scheduler invocations.             |
| Business owners confused by WhatsApp credit model             | Medium      | Medium | In-dashboard tooltip and onboarding copy explain prepaid model; low-balance email acts as early warning; cap is always transparent.              |
| Meta IP allowlist not updated (webhook rejected)              | Low         | High   | Document Meta IP ranges in `config/leo.php`; add monitoring alert if WhatsApp webhook returns 403 spike.                                        |

---

## Section 8 — External Dependencies

| Service / Library              | Phase 6 Usage                                                          | Fallback if Unavailable                                               |
|--------------------------------|------------------------------------------------------------------------|-----------------------------------------------------------------------|
| Meta WhatsApp Cloud API        | Inbound webhook + outbound message delivery                            | Fall back to Telegram channel; notify business by email               |
| Meta Business Manager          | Phone number verification + System User token generation               | Development uses Meta test number (always available without approval) |
| Stripe API                     | Checkout session for top-up + Invoice Item for monthly auto-renewal    | Manual credit via Artisan command `leo:manual-topup {business} {cents}` |
| Redis                          | Existing Léo session + throttle (unchanged from Phase 5)              | No new Redis dependency added in Phase 6                              |
| Laravel Scheduler              | `leo:renew-whatsapp-credits` monthly, `whatsapp:purge-windows` daily  | Manual Artisan invocation at month start                              |

---

## Assumptions

> The following assumptions were made during spec generation. Review and adjust before implementation begins.

- A single shared Meta phone number is used for all businesses on the platform (MVP). Each `leo_channel` for WhatsApp uses the same `WHATSAPP_PHONE_NUMBER_ID` but a different `external_identifier` (sender's phone number). Business is resolved from the inbound sender phone number, not from a dedicated number per business.
- WhatsApp conversation costs are approximated at the application level (5 cents service / 10 cents utility). Exact Meta billing is not available in real-time via API. Monthly review of actual Meta invoices vs. platform credits is a manual reconciliation step.
- Credits roll over indefinitely (no expiry). Unused credits from one month carry to the next. This is simpler, fairer, and avoids accounting complexity.
- The monthly auto-renewal is charged via Stripe Invoice (not a new Subscription). The Léo 9€/month add-on subscription created in Phase 5 is unchanged. WhatsApp credits are separate one-time or scheduled Invoice Items.
- `Business` model has `stripe_customer_id` available (set during Phase 3 Stripe integration) — required for creating Invoice Items.
- The Telegram channel remains completely unchanged. Business owners choose either Telegram or WhatsApp (existing `UNIQUE business_id` constraint on `leo_channels` enforces one channel per business).
- A business owner wanting to switch from Telegram to WhatsApp must delete their Telegram channel first (existing delete+recreate flow from Phase 5). No in-place migration.
- `whatsapp_last_renewed_at` is the sole idempotency guard for monthly renewal. If this column is NULL or the date is in a prior month, the renewal job is allowed to run.
