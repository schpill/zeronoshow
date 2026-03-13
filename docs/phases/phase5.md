# Phase 5 — Léo: AI Assistant for Business Owners

| Field            | Value                                                                                          |
|------------------|-----------------------------------------------------------------------------------------------|
| **Phase**        | 5 of 5                                                                                         |
| **Name**         | Léo — Conversational AI Assistant (Telegram-first, channel-agnostic)                          |
| **Duration**     | Weeks 7–10 (4 weeks)                                                                           |
| **Milestone**    | M5 — At least 3 pilot businesses actively using Léo daily via Telegram                        |
| **PRD Sections** | §Leo PRD (docs/leo_expression_de_besoins.md)                                                  |
| **Prerequisite** | Phase 4 fully completed and validated (all exit criteria met, production live)                 |
| **Status**       | Not started                                                                                    |

---

## Section 1 — Phase Objectives

| ID        | Objective                                                                                                      | Verifiable?                                      |
|-----------|----------------------------------------------------------------------------------------------------------------|--------------------------------------------------|
| P5-OBJ-1  | A business owner can activate Léo for an establishment, choose one channel type, and link it                  | Feature test passes                              |
| P5-OBJ-2  | Léo correctly routes inbound messages to the right establishment (one channel per business enforced)          | Feature test passes                              |
| P5-OBJ-3  | Gemini 2.5 Flash API with function calling answers all 5 Léo intents (stats, pending, upcoming, cancelled, details) | Feature test + integration test passes      |
| P5-OBJ-4  | A pro managing multiple establishments receives a channel-selection prompt when one sender manages many       | Feature test passes                              |
| P5-OBJ-5  | Léo proactively notifies the business owner on cancellation and no-show events via Telegram                   | Feature test passes (SendLeoNotificationJob)     |
| P5-OBJ-6  | The channel abstraction supports adding a new channel implementation with no core logic changes                | Code review validates interface compliance       |
| P5-OBJ-7  | Léo add-on activated as Stripe Subscription Item on existing subscription; deactivated on cancellation        | Feature test passes                              |
| P5-OBJ-8  | Dashboard Léo page: configure channel (one per business), activate, deactivate, change channel (delete+create)| E2E test passes                                  |
| P5-OBJ-9  | Outbound message throttle blocks anti-loop scenarios (> 20 messages/hour per channel)                         | Unit test passes                                 |
| P5-OBJ-10 | Public landing page implemented from template_site_vitrine.html, `/dashboard` route working                   | E2E test passes                                  |
| P5-OBJ-11 | Backend test coverage ≥ 80%, frontend ≥ 80%                                                                   | CI coverage gate passes                          |

---

## Section 2 — Entry Criteria

- Phase 4 exit criteria all validated (all items checked in phase4.md Section 6)
- Production environment live at `zeronoshow.fr` with TLS, Sentry, Horizon operational
- Stripe subscription billing fully operational (Phase 3 prerequisite)
- Telegram Bot API credentials (bot token) available in `.env`
- Gemini API key available in `.env` as `GEMINI_API_KEY` (for Gemini 2.5 Flash function calling)
- `CLAUDE.md` updated to reference Phase 5 tasks

---

## Section 3 — Scope — Requirement Traceability

| Requirement Group              | Source Ref                     | Included?  | Notes                                                                        |
|-------------------------------|-------------------------------|------------|------------------------------------------------------------------------------|
| Telegram channel MVP           | Leo PRD §4, §7                | Yes        | Full — inbound + outbound via Telegram Bot API. One channel per business.    |
| WhatsApp Business API          | Leo PRD §4                    | No         | Deferred — pricing model TBD (per-message cost makes 9€ flat unviable). Channel interface ready. |
| SMS fallback channel           | Leo PRD §4                    | Partial    | Infrastructure only (TwilioChannel stub). Full delivery deferred.            |
| Slack / Discord channels       | Leo PRD §4 (future)           | No         | Channel interface makes addition trivial in future phase. Pricing: 9€ flat.  |
| Inbound intent resolution      | Leo PRD §5, System Prompt     | Yes        | Gemini 2.5 Flash API with function calling (no MeiliSearch)                  |
| Multi-establishment routing    | Conversation context           | Yes        | Redis session per sender+channel with TTL; selection prompt when ambiguous   |
| Anti-loop throttle             | Conversation context           | Yes        | Redis counter: max 20 outbound messages/hour per channel (guard, not billing)|
| Push notifications (outbound)  | Leo PRD §9                    | Yes        | Cancellation + no-show triggers via SendLeoNotificationJob                   |
| Léo add-on billing             | Leo PRD §10, conversation     | Yes        | 9€/month as Stripe Subscription Item on existing subscription. One channel per business. |
| Channel change flow            | Conversation context           | Yes        | No in-place migration — business deactivates old, creates new. UI warns clearly. |
| Dashboard — Léo management     | Leo PRD §7, conversation      | Yes        | Vue page: single channel config per business (create, activate, deactivate, delete+recreate) |
| Public landing page (vitrine)  | template_site_vitrine.html    | Yes        | Implement from existing HTML template. Route `/` → LandingView (public).    |
| Vue Router refactor            | Conversation context           | Yes        | `/` → LandingView, `/dashboard` → Dashboard. `guestOnly` redirects → `/dashboard`. |
| V2 features (create/modify rez)| Leo PRD §6                    | No         | Deferred — out of scope for Phase 5                                          |
| Automatic client call          | Leo PRD §6                    | No         | Deferred                                                                     |
| Waitlist                       | Leo PRD §6                    | No         | Deferred                                                                     |

---

## Section 4 — Detailed Sprint Breakdown

### 4.5 Sprint 5 — Léo Core Backend (Weeks 7–8)

#### 4.5.1 Sprint Objectives

- Channel abstraction interface defined and Telegram implementation delivered
- Inbound Telegram webhook processed end-to-end (receive → resolve business → call Gemini → respond)
- All 5 Léo tools implemented as Laravel actions querying existing repositories
- Multi-establishment session routing via Redis operational
- Outbound notification job delivered and dispatched by ReservationObserver

---

#### 4.5.2 Database Migrations

| Migration name                        | Description                                                                                                                                                                                                                                                                                                                                                                          |
|---------------------------------------|--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `create_leo_channels_table`           | id UUID PK, business_id UUID FK→businesses(id) ON DELETE CASCADE NOT NULL UNIQUE (one channel per business), channel ENUM('telegram','whatsapp','sms','slack','discord') NOT NULL DEFAULT 'telegram', external_identifier VARCHAR(255) NOT NULL (Telegram chat_id / WhatsApp number / Slack channel), bot_name VARCHAR(100) NOT NULL DEFAULT 'Léo', is_active BOOLEAN NOT NULL DEFAULT true, created_at TIMESTAMPTZ, updated_at TIMESTAMPTZ. UNIQUE(business_id) — enforces one channel per business. Indexes: business_id (btree unique), channel (btree), external_identifier (btree). |
| `create_leo_sessions_table`           | id UUID PK, channel_id UUID FK→leo_channels(id) ON DELETE CASCADE NOT NULL, sender_identifier VARCHAR(255) NOT NULL (phone or Telegram user_id), active_business_id UUID FK→businesses(id) ON DELETE SET NULL nullable, expires_at TIMESTAMPTZ NOT NULL, created_at TIMESTAMPTZ, updated_at TIMESTAMPTZ. UNIQUE(channel_id, sender_identifier). Index: expires_at (btree) for TTL cleanup. |
| `create_leo_message_logs_table`       | id UUID PK, channel_id UUID FK→leo_channels(id) ON DELETE CASCADE NOT NULL, direction ENUM('inbound','outbound') NOT NULL, sender_identifier VARCHAR(255) NOT NULL, raw_message TEXT NOT NULL, intent VARCHAR(100) nullable, tool_called VARCHAR(100) nullable, response_preview VARCHAR(500) nullable, tokens_used INT nullable, latency_ms INT nullable, created_at TIMESTAMPTZ. Index: channel_id+created_at composite (btree), direction (btree). |

---

#### 4.5.3 Back-end Tasks

| ID         | Task                                                                                                                                                                                                                                                              | PRD Ref            |
|------------|-------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|--------------------|
| P5-BE-001  | Create `app/Contracts/LeoChannelInterface.php` — define interface with methods: `sendMessage(string $recipientId, string $text): void`, `parseInbound(Request $request): LeoInboundMessage`, `verifyWebhook(Request $request): bool`                            | Leo PRD §7         |
| P5-BE-002  | Create `app/Leo/DTOs/LeoInboundMessage.php` — readonly DTO with fields: `channelType: string`, `senderId: string`, `messageText: string`, `rawPayload: array`. Use PHP 8.3 readonly properties.                                                                  | Leo PRD §7         |
| P5-BE-003  | Create `app/Leo/Channels/TelegramChannel.php` implementing `LeoChannelInterface` — `parseInbound()` extracts `message.from.id` and `message.text` from Telegram Update payload; `verifyWebhook()` validates `X-Telegram-Bot-Api-Secret-Token` header; `sendMessage()` calls Telegram Bot API `sendMessage` endpoint with `chat_id` and `text`; throws `LeoChannelException` on HTTP errors | Leo PRD §4, §7     |
| P5-BE-004  | Create `app/Leo/Channels/TwilioSmsChannel.php` implementing `LeoChannelInterface` — stub implementation: `parseInbound()` and `verifyWebhook()` return `null`/`false`, `sendMessage()` throws `LeoChannelNotImplementedException`. Documents extension point for future delivery. | Leo PRD §4         |
| P5-BE-005  | Register channel implementations in `AppServiceProvider` — bind `LeoChannelInterface` via factory keyed by channel enum value; e.g. `'telegram' => TelegramChannel::class`; add `TELEGRAM_BOT_TOKEN` and `TELEGRAM_WEBHOOK_SECRET` to `.env.example` and config  | Leo PRD §7         |
| P5-BE-006  | Create migration `create_leo_channels_table` per Section 4.5.2 — run `php artisan make:migration` and implement `up()/down()` with Blueprint; add `ENUM` cast in model                                                                                           | Leo PRD §8         |
| P5-BE-007  | Create migration `create_leo_sessions_table` per Section 4.5.2                                                                                                                                                                                                    | Leo PRD §8         |
| P5-BE-008  | Create migration `create_leo_message_logs_table` per Section 4.5.2                                                                                                                                                                                               | Leo PRD §8         |
| P5-BE-009  | Create `app/Models/LeoChannel.php` — `HasUuids`, `belongsTo(Business::class)`, cast `channel` to `ChannelTypeEnum`, cast `is_active`/`leo_addon_active` to bool; scope `active()` filters `is_active=true AND leo_addon_active=true`                             | Leo PRD §8         |
| P5-BE-010  | Create `app/Models/LeoSession.php` — `HasUuids`, `belongsTo(LeoChannel::class)`, `belongsTo(Business::class, 'active_business_id')`; scope `forSender(string $channelId, string $senderId)` filters by both fields; scope `valid()` filters `expires_at > now()` | Leo PRD §8         |
| P5-BE-011  | Create `app/Models/LeoMessageLog.php` — `HasUuids`, `belongsTo(LeoChannel::class)`, cast `direction` to enum; no `updated_at` (insert-only log)                                                                                                                  | Leo PRD §9         |
| P5-BE-012  | Create `app/Leo/Services/LeoSessionService.php` — `resolve(string $channelId, string $senderId): ?Business` retrieves active session from `leo_sessions` table; `set(string $channelId, string $senderId, string $businessId, int $ttlSeconds = 300): void` upserts session row; `clear(string $channelId, string $senderId): void` deletes row; handles expired rows via `valid()` scope | Leo PRD §8, convo  |
| P5-BE-013  | Create `app/Leo/Services/LeoBusinessResolver.php` — `resolve(string $channelType, string $externalIdentifier): LeoResolutionResult` queries `leo_channels` where channel+external_identifier+is_active+leo_addon_active; returns: SINGLE business (resolved), MULTIPLE businesses (needs selection), NONE (unknown sender); uses `LeoSessionService` to check existing active session first | Leo PRD §8, convo  |
| P5-BE-014  | Create `app/Leo/Tools/GetTodayStatsTool.php` — `execute(string $businessId): array` queries `reservations` for today (`DATE(reserved_at) = CURDATE()`), groups by status, returns `['total', 'confirmed', 'pending', 'cancelled', 'no_show', 'show', 'score_avg']` | Leo PRD §5.1, §5.4 |
| P5-BE-015  | Create `app/Leo/Tools/GetPendingReservationsTool.php` — `execute(string $businessId): array` queries reservations with `status=pending` for today, ordered by `reserved_at ASC`, returns array of `['time' => 'HH:MM', 'name' => client_name, 'guests' => party_size]` (never exposes phone numbers) | Leo PRD §5.2       |
| P5-BE-016  | Create `app/Leo/Tools/GetUpcomingReservationsTool.php` — `execute(string $businessId, int $limit = 5): array` queries reservations with `reserved_at > now()` ordered by `reserved_at ASC`, limit parameter, returns same shape as P5-BE-015                    | Leo PRD §5.5       |
| P5-BE-017  | Create `app/Leo/Tools/GetCancelledReservationsTool.php` — `execute(string $businessId): array` queries reservations with `status IN (cancelled_by_client, cancelled_by_business)` for today, ordered by `reserved_at ASC`, returns `['time', 'name', 'guests', 'cancelled_at']` | Leo PRD §5.3       |
| P5-BE-018  | Create `app/Leo/Tools/GetReservationDetailsTool.php` — `execute(string $businessId, string $query): array` searches reservations by client name (case-insensitive LIKE) or time (HH:MM match) for today, returns full detail row (never phone number); returns empty array if not found | Leo PRD §5, convo  |
| P5-BE-019  | Create `app/Leo/Services/LeoGeminiService.php` — `ask(string $businessId, string $botName, string $userMessage): string` builds Gemini `generateContent` request via Laravel HTTP client; injects system prompt from `leo_expression_de_besoins.md` §Prompt with `$botName` substituted; defines 5 tools as Gemini `functionDeclarations`; handles `functionCall` response part by dispatching to the corresponding Tool class; appends `functionResponse` part, makes second Gemini API call; returns final text response. Model: `gemini-2.5-flash`. Base URL: `https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key={GEMINI_API_KEY}`. Max 2 function calls per turn. | Leo PRD §7, convo  |
| P5-BE-020  | Create `app/Http/Controllers/LeoWebhookController.php` — `telegram(Request $request): Response` — verifies webhook secret via `TelegramChannel::verifyWebhook()`, parses inbound message, resolves business via `LeoBusinessResolver`, handles three cases: (a) NONE → responds "Numéro non reconnu", (b) MULTIPLE → responds with numbered list and sets partial session, (c) SINGLE → calls `LeoGeminiService::ask()` and sends response. Logs to `leo_message_logs`. Returns HTTP 200 always (Telegram retries on non-200). | Leo PRD §7         |

---

#### 4.5.4 Back-end Tests (TDD)

| Test File                                                         | Test Cases                                                                                                                                                                                                                                                                                                       |
|-------------------------------------------------------------------|------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `tests/Unit/Leo/TelegramChannelTest.php`                         | parses valid Telegram Update payload correctly, returns null for non-message update, verifyWebhook passes with correct secret, verifyWebhook fails with wrong secret, sendMessage dispatches correct HTTP payload                                                                                                  |
| `tests/Unit/Leo/LeoSessionServiceTest.php`                       | resolve returns null when no session exists, resolve returns null when session expired, resolve returns Business when valid session exists, set creates new session row, set updates existing session row (upsert), clear deletes existing session, clear is idempotent when no session exists                      |
| `tests/Unit/Leo/LeoBusinessResolverTest.php`                     | returns NONE when no channel matches, returns SINGLE when exactly one active channel matches, returns MULTIPLE when two active channels share sender identifier, returns NONE when channel exists but addon inactive, returns NONE when channel exists but is_active=false, checks session first before querying channels |
| `tests/Unit/Leo/Tools/GetTodayStatsToolTest.php`                 | returns zeros when no reservations today, counts correctly for each status, includes show and no_show in total, score_avg is null when no confirmed reservations                                                                                                                                                   |
| `tests/Unit/Leo/Tools/GetPendingReservationsToolTest.php`        | returns empty array when no pending, returns reservations ordered by time, never includes phone_number field in returned data, only returns today's reservations                                                                                                                                                    |
| `tests/Unit/Leo/Tools/GetUpcomingReservationsToolTest.php`       | returns empty when none upcoming, respects limit parameter, only returns future reservations (not past), ordered ascending by reserved_at                                                                                                                                                                          |
| `tests/Unit/Leo/Tools/GetCancelledReservationsToolTest.php`      | returns both cancelled_by_client and cancelled_by_business statuses, excludes cancellations from other days, never exposes phone number                                                                                                                                                                            |
| `tests/Unit/Leo/Tools/GetReservationDetailsToolTest.php`         | finds reservation by partial name match (case-insensitive), finds reservation by time string HH:MM, returns empty array when not found, returns empty array when business_id mismatch, never returns phone_number field                                                                                             |
| `tests/Feature/Leo/TelegramWebhookTest.php`                      | returns 200 for unknown sender with "non reconnu" message, returns 200 and responds with stats for known single-business sender, returns 200 with establishment selection prompt for multi-business sender, returns 200 when webhook secret invalid (silent — no retry trigger), logs inbound+outbound to leo_message_logs, channel with leo_addon_active=false is treated as unknown sender |

---

#### 4.5.5 Front-end Tasks

| ID         | Task                                                                                                                                                                                                                   | PRD Ref      |
|------------|------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|--------------|
| P5-FE-001  | Create `src/api/leo.ts` — typed API client functions: `getLeoChannels(): Promise<LeoChannel[]>`, `createLeoChannel(payload: CreateLeoChannelPayload): Promise<LeoChannel>`, `updateLeoChannel(id: string, payload: Partial<LeoChannel>): Promise<LeoChannel>`, `deleteLeoChannel(id: string): Promise<void>`. All use existing `axios` instance. Define `LeoChannel` and `CreateLeoChannelPayload` TypeScript interfaces. | Leo PRD §7   |
| P5-FE-002  | Create `src/composables/useLeoChannels.ts` — Composition API composable: `channels: Ref<LeoChannel[]>`, `loading: Ref<boolean>`, `error: Ref<string \| null>`, `fetchChannels()`, `createChannel(payload)`, `updateChannel(id, payload)`, `deleteChannel(id)`. Handle optimistic UI updates for toggle. | Leo PRD §7   |
| P5-FE-003  | Create `src/components/leo/LeoChannelCard.vue` — displays one channel: bot name, channel type badge (Telegram/WhatsApp/SMS), active toggle (emits `toggle`), rename button (inline edit), delete button with confirmation dialog. Props: `channel: LeoChannel`. Emits: `updated`, `deleted`. ARIA labels on all interactive elements. | Leo PRD §7   |
| P5-FE-004  | Create `src/components/leo/CreateLeoChannelForm.vue` — form to create a new Léo channel. Fields: bot name (required, max 100 chars), channel type (select: Telegram only for now, others disabled with "Bientôt disponible" tooltip), Telegram external identifier (required when channel=telegram, label: "Chat ID Telegram"). Validates client-side before submit. Emits `created` on success. | Leo PRD §7   |
| P5-FE-005  | Create `src/views/LeoView.vue` — page at `/leo` route: header with "Mes assistants Léo" title + "Ajouter un assistant" button; renders `LeoChannelCard` for each channel; shows `CreateLeoChannelForm` in a modal when button clicked; shows empty state illustration when no channels exist; uses `useLeoChannels` composable. | Leo PRD §7   |
| P5-FE-006  | Add `/leo` route to `src/router/index.ts` — `requiresAuth: true`, lazy-loaded `LeoView`, add "Léo" nav link to sidebar with bot icon. Guard: if `leo_addon_active` false on all businesses, show upgrade banner instead of channel list. | Leo PRD §7   |

---

#### 4.5.6 Front-end Tests

| Test File                                          | Test Cases                                                                                                                                                                                                                                       |
|----------------------------------------------------|--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `src/composables/__tests__/useLeoChannels.test.ts` | fetchChannels populates channels on success, fetchChannels sets loading true then false, fetchChannels sets error on API failure, deleteChannel removes channel from list optimistically, updateChannel patches channel in list                    |
| `src/components/leo/__tests__/LeoChannelCard.test.ts` | renders bot name and channel type, toggle emits updated event with new active state, delete button shows confirmation dialog, confirm delete emits deleted event, cancel delete keeps channel in list, rename enters inline edit mode            |
| `src/components/leo/__tests__/CreateLeoChannelForm.test.ts` | shows validation error when bot name is empty, shows validation error when chat ID is empty for telegram, submits form with valid data, emits created on success, shows server error on API failure, disables non-telegram channel options     |
| `src/views/__tests__/LeoView.test.ts`              | renders empty state when no channels, renders channel cards for each channel, opens create form modal on button click, shows upgrade banner when addon inactive                                                                                   |

---

#### 4.5.7 DevOps / Infrastructure Tasks

| ID         | Task                                                                                                                                                                                                                     | PRD Ref  |
|------------|--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|----------|
| P5-DO-001  | Add `TELEGRAM_BOT_TOKEN`, `TELEGRAM_WEBHOOK_SECRET`, `GEMINI_API_KEY` to `.env.example` and `config/services.php` — add `telegram` key with `token` and `webhook_secret`; add `gemini` key with `api_key` and `model` (default: `gemini-2.5-flash`) | Leo PRD §7 |
| P5-DO-002  | No additional PHP package required — use Laravel HTTP client (`Illuminate\Support\Facades\Http`) for Gemini REST API calls; document Gemini API base URL and payload format in `config/services.php` comments | Leo PRD §7 |
| P5-DO-003  | Add `POST /webhooks/leo/telegram` to `routes/api.php` without auth middleware (Telegram calls this) — add IP allowlist middleware for Telegram IP ranges via `config/leo.php`                                            | Leo PRD §7 |

---

#### 4.5.8 Deliverables Checklist

- [ ] `leo_channels`, `leo_sessions`, `leo_message_logs` tables migrated and verified
- [ ] `LeoChannelInterface` contract defined with 3 methods
- [ ] `TelegramChannel` implements all 3 interface methods with tests passing
- [ ] `TwilioSmsChannel` stub present and documented as extension point
- [ ] All 5 Leo tools return correct data without exposing phone numbers
- [ ] `LeoGeminiService` calls Gemini 2.5 Flash API with function calling and handles 2-step conversation
- [ ] `LeoWebhookController::telegram` handles NONE / SINGLE / MULTIPLE resolution cases
- [ ] `LeoSessionService` correctly persists and expires sessions
- [ ] All backend unit + feature tests passing (`pest --stop-on-failure`)
- [ ] Frontend composable and components created and tested
- [ ] `/leo` route accessible with `requiresAuth`
- [ ] Environment variables documented in `.env.example`

---

### 4.6 Sprint 6 — Notifications, Billing & Polish (Weeks 9–10)

#### 4.6.1 Sprint Objectives

- Outbound push notifications dispatched automatically on business-critical events (cancellation, no-show)
- Stripe add-on subscription enforced: Léo channels deactivated when subscription lapses
- Full dashboard polish: Léo activation flow with add-on upgrade prompt
- Multi-establishment selection flow implemented and tested
- System prompt personalised per bot_name and establishment name
- Message logging visible in Horizon for debugging

---

#### 4.6.2 Database Migrations

| Migration name                             | Description                                                                                                                                                                   |
|--------------------------------------------|-------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `add_leo_addon_to_businesses_table`        | Add `leo_addon_active BOOLEAN NOT NULL DEFAULT false`, `leo_addon_stripe_item_id VARCHAR(255) nullable` (Stripe Subscription Item ID, set on activation, nulled on cancellation) to `businesses` table. Index: `leo_addon_active` (btree).            |

---

#### 4.6.3 Back-end Tasks

| ID         | Task                                                                                                                                                                                                                                                                               | PRD Ref            |
|------------|------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|--------------------|
| P5-BE-030  | Create `app/Jobs/SendLeoNotificationJob.php` — `handle()`: receives `LeoChannel $channel`, `string $eventType` (cancellation\|no_show\|slot_freed), `array $payload`; resolves channel implementation via `LeoChannelInterface`; builds French notification message from event type + payload; calls `sendMessage()`; logs to `leo_message_logs` with `direction=outbound`; retries 3× on failure with exponential backoff | Leo PRD §9         |
| P5-BE-031  | Update `app/Observers/ReservationObserver.php` — in `updated()` method: when `status` transitions to `cancelled_by_client` or `no_show`, dispatch `SendLeoNotificationJob` for all active Leo channels of the reservation's business; pass reservation client name, time, party size (never phone) | Leo PRD §9, convo  |
| P5-BE-032  | Create `app/Leo/Services/LeoMultiBusinessSelectionService.php` — `buildSelectionPrompt(Collection $channels): string` returns French numbered list "Pour quel établissement ?\n1. Brasserie du Port\n2. Burger Factory"; `parseSelection(string $userMessage, Collection $channels): ?LeoChannel` parses user reply ("1", "2", or establishment name match); handles invalid input (returns null with prompt to retry) | Leo PRD §8, convo  |
| P5-BE-033  | Update `LeoWebhookController::telegram()` — integrate `LeoMultiBusinessSelectionService`: when resolution is MULTIPLE and session has no active_business_id, respond with selection prompt and store partial session (active_business_id=null, pending_selection=true in session); on next message, if pending_selection, attempt `parseSelection()` first | Leo PRD §8, convo  |
| P5-BE-034  | Update `LeoGeminiService::ask()` — personalise system prompt with `$botName` (replace "Léo" placeholder) and `$establishmentName` (append "You are managing reservations for {name}."); pull establishment name from `Business::name` | Leo PRD system prompt |
| P5-BE-035  | Create `app/Http/Middleware/LeoAddonActiveMiddleware.php` — verify `business.leo_addon_active = true`; return 402 JSON `{"message": "Léo add-on inactif. Veuillez souscrire pour utiliser cette fonctionnalité."}` if not active                                                   | Leo PRD §10, convo |
| P5-BE-036  | Create `app/Http/Controllers/LeoChannelController.php` — CRUD controller protected by `auth:sanctum` + `LeoAddonActiveMiddleware` on store/update/destroy (not on index/show): `index()` returns the single channel for authenticated business (or empty); `store(StoreLeoChannelRequest)` creates channel — fails with 409 if channel already exists for this business; `update(UpdateLeoChannelRequest, LeoChannel)` updates name/is_active; `destroy(LeoChannel)` deletes (hard delete, business must recreate to change channel); authorises via Gate that channel belongs to authenticated business | Leo PRD §7         |
| P5-BE-037  | Create `app/Http/Requests/StoreLeoChannelRequest.php` — validate: `bot_name` required\|string\|max:100, `channel` required\|in:telegram,whatsapp,sms,slack,discord, `external_identifier` required\|string\|max:255; French error messages; custom rule: fails with 409 message if `leo_channels` row already exists for this business (`UNIQUE business_id` guard at DB level, caught and returned as 409 in controller) | Leo PRD §7         |
| P5-BE-038  | Create `app/Http/Requests/UpdateLeoChannelRequest.php` — validate: `bot_name` sometimes\|string\|max:100, `is_active` sometimes\|boolean; French error messages. Note: channel type and external_identifier cannot be updated — delete + recreate to change them.                  | Leo PRD §7, convo  |
| P5-BE-039  | Create `app/Http/Resources/LeoChannelResource.php` — expose: id, business_id, channel, external_identifier (masked: last 4 chars only, e.g. "****1234"), bot_name, is_active, created_at; never expose raw Telegram tokens                                                        | Leo PRD §7         |
| P5-BE-040  | Create `app/Console/Commands/PurgeLeoMessageLogs.php` — `leo-logs:purge {--days=90}` deletes `leo_message_logs` rows older than `--days` days; logs count to Laravel Log; register in `app/Console/Kernel.php` to run `monthly()`                                                 | Leo PRD §9         |
| P5-BE-041  | Create `app/Leo/Services/LeoThrottleService.php` — `isThrottled(string $channelId): bool` checks Redis key `leo:throttle:{channelId}:{YYYY-MM-DD-HH}` (hourly bucket); `increment(string $channelId): void` increments counter with TTL 3600s; threshold: 20 outbound messages/hour (configurable via `config/leo.php`); protects against infinite loops, not billing                                                                                                    | Convo              |
| P5-BE-042  | Update `SendLeoNotificationJob::handle()` to call `LeoThrottleService::isThrottled()` before sending; if throttled, log warning to `leo_message_logs` with `intent='throttled'` and skip send silently                                                                             | Convo              |
| P5-BE-043  | Create Stripe product + price for Léo add-on in `database/seeders/LeoAddonSeeder.php` — creates Stripe product "Léo Assistant" + recurring price 9€/month via Stripe API; stores price ID in `config/leo.php`; idempotent. Document in `docs/dev/phase5.md` Audit Notes.         | Leo PRD §10, convo |
| P5-BE-044  | Create `app/Http/Controllers/LeoAddonController.php` — `activate(): JsonResponse` calls `stripe.subscriptionItems.create()` on `business.stripe_subscription_id` with Léo price ID and `proration_behavior: create_prorations`; stores returned `stripe_item_id` in `businesses.leo_addon_stripe_item_id`; sets `leo_addon_active = true`; returns `{checkout_url: null, activated: true}`. `deactivate(): JsonResponse` calls `stripe.subscriptionItems.delete(stripe_item_id)`; sets `leo_addon_active = false`, nulls `leo_addon_stripe_item_id`, sets `leo_channels.is_active = false`. `status(): JsonResponse` returns `{active: bool, stripe_item_id: string\|null}`.   | Leo PRD §10, convo |
| P5-BE-045  | Update `StripeWebhookController` (Phase 3) — handle `customer.subscription.updated` and `customer.subscription.deleted`: if event concerns Léo add-on price ID, sync `businesses.leo_addon_active` and `leo_channels.is_active` accordingly (failsafe in case of direct Stripe portal cancellation) | Leo PRD §10        |
| P5-BE-046  | Create Artisan command `leo:setup-telegram-webhook` — calls Telegram Bot API `setWebhook` with URL `{APP_URL}/webhooks/leo/telegram` and `secret_token`; also calls `deleteWebhook` first to clear stale registration; outputs confirmation; run once at deploy                   | Leo PRD §7         |

---

#### 4.6.4 Back-end Tests (TDD)

| Test File                                                            | Test Cases                                                                                                                                                                                                                                                                              |
|----------------------------------------------------------------------|-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `tests/Unit/Leo/SendLeoNotificationJobTest.php`                     | dispatches correct message for cancellation event, dispatches correct message for no_show event, logs outbound message to leo_message_logs, retries on channel exception, does not expose phone number in message payload                                                                |
| `tests/Feature/Leo/ReservationObserverLeoNotificationTest.php`      | dispatches SendLeoNotificationJob when reservation status changes to cancelled_by_client, dispatches job when status changes to no_show, does not dispatch when status change is not cancellation or no_show, does not dispatch when business has no active Leo channels                 |
| `tests/Unit/Leo/LeoMultiBusinessSelectionServiceTest.php`           | buildSelectionPrompt generates numbered French list, parseSelection returns correct channel for numeric input "1", parseSelection returns correct channel for numeric input "2", parseSelection returns null for out-of-range number, parseSelection returns null for unrecognised input  |
| `tests/Feature/Leo/LeoChannelCrudTest.php`                          | index returns null when no channel exists, index returns channel for authenticated business, index returns 401 for unauthenticated, store creates channel when addon active, store returns 402 when addon inactive, store returns 409 when channel already exists for business (UNIQUE violation), store returns 422 on validation failure, update renames channel, update toggles is_active, destroy deletes channel and allows recreating a new one, destroy returns 404 for other business's channel |
| `tests/Feature/Leo/LeoAddonBillingTest.php`                         | activate creates Stripe Subscription Item and sets leo_addon_active=true, activate stores stripe_item_id on business, activate returns 402 when business has no active Stripe subscription, deactivate calls Stripe deleteItem and sets leo_addon_active=false, deactivate sets leo_channels.is_active=false, status returns correct active state |
| `tests/Unit/Leo/LeoThrottleServiceTest.php`                         | isThrottled returns false when under threshold, isThrottled returns true when threshold reached, increment increases Redis counter, counter expires after 3600s, threshold is configurable via config |
| `tests/Feature/Leo/StripeWebhookLeoAddonTest.php`                   | activates leo_addon_active on subscription created for Leo price, deactivates leo_addon_active on subscription cancelled, deactivates all leo_channels when addon deactivated, ignores webhook for non-Leo price IDs                                                                     |
| `tests/Unit/Leo/PurgeLeoMessageLogsCommandTest.php`                  | deletes records older than default 90 days, respects custom --days option, preserves records within retention window, outputs count of deleted records                                                                                                                                   |

---

#### 4.6.5 Front-end Tasks

| ID         | Task                                                                                                                                                                                                                                                                       | PRD Ref      |
|------------|----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|--------------|
| P5-FE-030  | Refactor `src/router/index.ts` — move Dashboard from `/` to `/dashboard`; add public `/` route → `LandingView` (no auth required); update `guestOnly` redirect from `'/'` to `'/dashboard'`; update `requiresAuth` redirect from `'/login'` to `'/login'` (unchanged); update `NavBar.vue` logo link from `/` to `/dashboard` | Convo        |
| P5-FE-031  | Create `src/views/LandingView.vue` — pixel-perfect Vue implementation of `docs/graphics/templates/template_site_vitrine.html`: Navbar (logo, nav links, Connexion + CTA buttons), Hero (copy + phone SMS mockup + floating badges), Stats band (3 KPIs), "Comment ça marche" (3 steps), Score de fiabilité section (dark bg, score cards), Pricing section (19€/mois card), Footer. All static content, no API calls. Tailwind classes matching template exactly. Responsive (mobile + desktop). | template_site_vitrine.html |
| P5-FE-032  | Update Pricing section in `LandingView.vue` — add Léo add-on card below the main pricing card: "Léo — Votre assistant Telegram" at 9€/mois, list features (résumé du jour, clients en attente, notifications annulations), badge "Add-on optionnel". Style: secondary card with brand-100 background, no border-brand-500 (reserved for main plan). | Leo PRD §10, convo |
| P5-FE-033  | Create `src/components/leo/LeoUpgradeBanner.vue` — displayed in LeoView when `leo_addon_active=false`: headline "Activez Léo", description "Votre assistant Telegram à 9€/mois", CTA "Activer Léo" calls `activateLeoAddon()` then refreshes addon status. Shows spinner during API call. Matches brand palette (colors.md). | Leo PRD §10  |
| P5-FE-034  | Update `src/views/LeoView.vue` — fetch `leo_addon_active` from business profile; show `LeoUpgradeBanner` when inactive; when active: show single `LeoChannelCard` if channel exists, or empty state with "Configurer Léo" button opening `CreateLeoChannelForm` modal; no list (one channel per business) | Leo PRD §7, convo  |
| P5-FE-035  | Create `src/components/leo/LeoChannelTypeBadge.vue` — pill badge: Telegram (blue, ✈ icon), WhatsApp (green, "Bientôt"), SMS (grey, "Bientôt"), Slack (purple, "Bientôt"), Discord (indigo, "Bientôt"). Props: `type: ChannelType`. "Bientôt" channels shown as disabled in `CreateLeoChannelForm`. | Leo PRD §7   |
| P5-FE-036  | Update `src/api/leo.ts` — add `activateLeoAddon(): Promise<{activated: boolean}>`, `deactivateLeoAddon(): Promise<void>`, `getLeoAddonStatus(): Promise<{active: boolean}>` | Leo PRD §10  |
| P5-FE-037  | Update `src/components/leo/CreateLeoChannelForm.vue` — channel type is a radio group (Telegram enabled, others disabled with "Bientôt" badge); add Telegram Chat ID setup accordion ("Comment obtenir votre Chat ID" → @userinfobot step-by-step); warn user: "Un seul canal possible par établissement. Pour changer de canal, supprimez l'actuel et créez-en un nouveau." | Leo PRD §7, convo  |
| P5-FE-038  | Update `src/components/leo/LeoChannelCard.vue` — add "Changer de canal" secondary button that triggers delete confirmation with explicit warning: "Cette action supprimera votre canal Léo actuel. Vous devrez en créer un nouveau. L'historique des messages sera conservé."; remove rename inline edit (bot_name only editable via update API) | Convo        |
| P5-FE-039  | Add Léo activity widget to `src/pages/Dashboard.vue` — small card showing last 3 Leo message logs (direction icon, intent label, timestamp); "Voir Léo →" link to `/leo`; hidden if `leo_addon_active=false` | Leo PRD §9   |

---

#### 4.6.6 Front-end Tests

| Test File                                                         | Test Cases                                                                                                                                                                                           |
|-------------------------------------------------------------------|------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `src/components/leo/__tests__/LeoUpgradeBanner.test.ts`          | renders headline and description, CTA button is present and has correct aria-label, clicking CTA calls activateLeoAddon and redirects to checkout_url, shows loading state during API call          |
| `src/components/leo/__tests__/LeoChannelTypeBadge.test.ts`       | renders Telegram badge with correct label and colour class, renders WhatsApp badge, renders SMS badge                                                                                                |
| `src/views/__tests__/LeoView.test.ts` (update)                   | shows upgrade banner when addon inactive, hides upgrade banner when addon active, renders channel list when active with channels, renders empty state when active with no channels                   |

---

#### 4.6.7 DevOps / Infrastructure Tasks

| ID         | Task                                                                                                                                                                                                                             | PRD Ref      |
|------------|----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|--------------|
| P5-DO-020  | Add `SendLeoNotificationJob` to queue worker config — ensure job runs on `default` queue (same as existing SMS jobs); add `QUEUE_CONNECTION=redis` verification in `.env.example`                                                | Leo PRD §9   |
| P5-DO-021  | Add `POST /api/v1/leo/channels` route group to `routes/api.php` protected by `auth:sanctum`; add `POST /api/v1/leo/addon/activate` for Stripe checkout redirect; document in Section 5 of this spec                             | Leo PRD §7   |
| P5-DO-022  | Document Telegram bot creation steps in `docs/dev/phase5.md` (Audit Notes) — BotFather flow: `/newbot`, set name, get token, set webhook via `php artisan leo:setup-telegram-webhook`                                           | Leo PRD §7   |
| P5-DO-023  | Update GitHub Actions CI workflow — add `TELEGRAM_BOT_TOKEN=fake` and `GEMINI_API_KEY=fake` to test environment secrets (for contract tests that mock HTTP via `Http::fake()`); ensure Leo feature tests run in CI              | Leo PRD §7   |

---

#### 4.6.8 Deliverables Checklist

- [ ] `SendLeoNotificationJob` dispatched on cancellation and no-show events
- [ ] Multi-business selection prompt working end-to-end via Telegram
- [ ] System prompt personalised with bot_name and establishment name
- [ ] `LeoChannelController` CRUD endpoints operational with auth + addon guard
- [ ] `LeoAddonActiveMiddleware` returns 402 when addon inactive
- [ ] Stripe webhook correctly activates/deactivates `leo_addon_active` on businesses
- [ ] `leo:setup-telegram-webhook` Artisan command operational
- [ ] `leo-logs:purge` command registered and tested
- [ ] `LeoUpgradeBanner` displayed when addon inactive
- [ ] `LeoChannelTypeBadge` renders all 3 channel types
- [ ] All sprint 6 backend + frontend tests passing
- [ ] CI passes with fake API keys in test environment

---

## Section 5 — API Endpoints Delivered in Phase 5

| Method | Endpoint                          | Controller                    | Auth          | Notes                                                                                                           |
|--------|-----------------------------------|-------------------------------|---------------|-----------------------------------------------------------------------------------------------------------------|
| POST   | `/webhooks/leo/telegram`          | `LeoWebhookController`        | No (webhook)  | Telegram Bot API webhook. Validates `X-Telegram-Bot-Api-Secret-Token`. Always returns 200.                     |
| GET    | `/api/v1/leo/channels`            | `LeoChannelController@index`  | Bearer        | Returns `LeoChannelResource[]` for authenticated business. No addon guard (read is always allowed).            |
| POST   | `/api/v1/leo/channels`            | `LeoChannelController@store`  | Bearer        | Body: `{bot_name, channel, external_identifier}`. Returns `LeoChannelResource` 201. 402 if addon inactive.     |
| PATCH  | `/api/v1/leo/channels/{id}`       | `LeoChannelController@update` | Bearer        | Body: `{bot_name?, is_active?}`. Returns updated `LeoChannelResource`. 402 if addon inactive.                  |
| DELETE | `/api/v1/leo/channels/{id}`       | `LeoChannelController@destroy`| Bearer        | Returns 204. 402 if addon inactive. 404 if not owned by authenticated business.                                |
| POST   | `/api/v1/leo/addon/activate`      | `LeoAddonController@activate` | Bearer        | Creates Stripe Checkout Session for Léo add-on price. Returns `{checkout_url: string}`.                       |
| GET    | `/api/v1/leo/addon/status`        | `LeoAddonController@status`   | Bearer        | Returns `{active: bool, stripe_item_id: string\|null}`.                                                        |

---

## Section 6 — Exit Criteria

| # | Criterion                                                                                                          | Validated |
|---|--------------------------------------------------------------------------------------------------------------------|-----------|
| 1 | All P5 functional requirements implemented: channel CRUD, Telegram inbound processing, 5 tools, notifications     | [ ]       |
| 2 | Backend test coverage ≥ 80% (measured via Pest + PCOV)                                                             | [ ]       |
| 3 | Frontend test coverage ≥ 80% (measured via Vitest)                                                                 | [ ]       |
| 4 | Pint (code style) passes with zero errors                                                                          | [ ]       |
| 5 | PHPStan level 8 passes with zero errors                                                                            | [ ]       |
| 6 | ESLint + Prettier check passes with zero errors                                                                    | [ ]       |
| 7 | All Pest tests pass (backend + feature + unit)                                                                     | [ ]       |
| 8 | All Vitest tests pass (frontend)                                                                                   | [ ]       |
| 9 | CI pipeline green on `main` (both backend and frontend jobs)                                                       | [ ]       |
| 10| Telegram webhook processes a real message end-to-end in staging (manual verification)                              | [ ]       |
| 11| Léo add-on billing correctly activates/deactivates channels via Stripe webhook (staging test)                      | [ ]       |
| 12| Multi-establishment selection flow tested with a real Telegram account owning 2 businesses (manual verification)   | [ ]       |
| 13| Phone numbers never appear in any Léo message log or outbound message (security audit)                             | [ ]       |
| 14| `leo-logs:purge` tested in staging with backdated test data                                                        | [ ]       |
| 15| `docs/dev/phase5.md` fully updated with all tasks marked `done` or `merged`                                        | [ ]       |

---

## Section 7 — Risks Specific to Phase 5

| Risk                                                          | Probability | Impact | Mitigation                                                                                                                    |
|---------------------------------------------------------------|-------------|--------|-------------------------------------------------------------------------------------------------------------------------------|
| Telegram Bot API webhook registration blocked by firewall     | Medium      | High   | Test `leo:setup-telegram-webhook` on staging before production; use ngrok for local dev; document fallback (polling mode)     |
| Gemini API latency > 3s causing Telegram timeout (5s limit)  | Medium      | High   | Use `gemini-2.5-flash` (fastest tier); implement async response (send "Léo réfléchit…" immediately, then follow-up message)  |
| GEMINI_API_KEY leak in logs                                   | Low         | High   | Never log request headers or query strings (key is in URL param); mask in Sentry config; rotate key immediately if exposed    |
| Telegram rate limits (30 messages/second per bot)             | Low         | Medium | Queue outbound notifications via `SendLeoNotificationJob`; never send synchronously in webhook handler                        |
| WhatsApp Business API validation delays (if added later)      | High        | Low    | Already mitigated: WhatsApp is out of scope; channel abstraction allows adding it without touching core logic                 |
| Stripe add-on price ID misconfiguration in production         | Low         | High   | `LeoAddonSeeder` is idempotent; add config validation in `AppServiceProvider` boot that throws if `leo.stripe_price_id` empty |
| Business owner sends personal data (names, addresses) to Léo | Medium      | Medium | System prompt instructs Léo never to store or repeat personal data; message log preview capped at 500 chars                   |

---

## Section 8 — External Dependencies

| Service / Library            | Phase 5 Usage                                              | Fallback if Unavailable                                          |
|------------------------------|------------------------------------------------------------|------------------------------------------------------------------|
| Telegram Bot API             | Inbound webhook + outbound message delivery                | Drop to SMS channel (stub ready); notify business by email       |
| Google Gemini 2.5 Flash API  | NLU + function calling for all inbound messages            | Fallback to strict pattern matching for the 7 command keywords   |
| Stripe API                   | Léo add-on subscription checkout + webhook enforcement     | Manual activation via Artisan command (`leo:activate {business}`) |
| Redis                        | Leo session storage (active_business_id per sender)        | Database fallback via `leo_sessions` table (already the design)  |
| Twilio (SMS)                 | Future SMS channel implementation (stub only in Phase 5)   | N/A — not active in Phase 5                                      |

---

## Assumptions

> The following assumptions were made during spec generation. Review and adjust before implementation begins.

- Gemini 2.5 Flash is called via REST API using Laravel HTTP client (`Http::post()`). The API key is passed as a query parameter: `?key={GEMINI_API_KEY}`. No additional PHP SDK is required. Payload format follows the Gemini `generateContent` REST schema with `tools[].functionDeclarations`.
- **One channel per business** is enforced at DB level via `UNIQUE(business_id)` on `leo_channels`. A 409 is returned if the business tries to create a second channel without deleting the first. Channel type cannot be changed in-place — delete + create is the documented flow. UI warns explicitly before delete.
- A single Telegram bot token is sufficient for Phase 5 MVP. Each `leo_channel` uses the same bot with a different `external_identifier` (chat_id). Multi-bot support (one bot per establishment) is a future enhancement.
- **WhatsApp pricing is intentionally undefined.** The per-message cost (~0,03–0,08€) makes the 9€ flat fee unviable. Pricing will be determined when WhatsApp is implemented (likely 19–29€/mois or pass-through). The channel abstraction is ready.
- **Outbound throttle** (20 messages/hour/channel via Redis) is an anti-loop guard, not a billing mechanism. Telegram API is free; there is no per-message cost to control.
- **Stripe Subscription Item**: Léo is activated by calling `stripe.subscriptionItems.create()` on the existing business subscription, not by creating a new subscription. `leo_addon_stripe_item_id` stored on `businesses` table. Proration is automatic.
- **Channel change** = delete + create. No in-place migration. `leo_message_logs` are preserved (FK to `leo_channels` set to ON DELETE SET NULL or retained). Business owner is responsible for informing their contacts.
- `Business` model from Phase 1 has a `name` field used for system prompt personalisation.
- The `ReservationObserver` from Phase 2 is the correct hook point for notification dispatch.
- Stripe is already integrated (Phase 3) with `businesses.stripe_subscription_id` available.
- The Gemini API key must never appear in Laravel logs or Sentry breadcrumbs — strip query string from logged URLs or use a wrapper that redacts the key before logging.
- Léo messages are always in the language of the business owner (French by default). The system prompt handles this.
- **Site vitrine**: `docs/graphics/templates/template_site_vitrine.html` is the pixel-perfect reference. The Vue implementation must match it exactly. The Léo pricing add-on card is a new addition not present in the original template.
