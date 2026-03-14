# Phase 7 — Smart Waitlist

| Field            | Value                                                                                        |
|------------------|----------------------------------------------------------------------------------------------|
| **Phase**        | 7 of 10                                                                                       |
| **Name**         | Smart Waitlist — Automatic slot recovery on cancellation                                     |
| **Duration**     | Weeks 15–18 (4 weeks)                                                                         |
| **Milestone**    | M7 — Cancellation automatically fills from waitlist within 15 minutes in at least 3 pilots  |
| **PRD Sections** | §Leo PRD V2 features — Waitlist                                                              |
| **Prerequisite** | Phase 6 fully completed and validated (WhatsApp channel live, ReservationObserver stable)    |
| **Status**       | Not started                                                                                   |

---

## Section 1 — Phase Objectives

| ID        | Objective                                                                                                               | Verifiable?                          |
|-----------|-------------------------------------------------------------------------------------------------------------------------|--------------------------------------|
| P7-OBJ-1  | A business owner can add a client to the waitlist for a given date/time slot from the dashboard                        | Feature test passes                  |
| P7-OBJ-2  | A client can self-register on the waitlist via a public tokenised link shared by the business owner                    | Feature test passes                  |
| P7-OBJ-3  | When a reservation is cancelled, NotifyWaitlistJob fires and notifies the top pending waitlist entry within 60 seconds | Feature test + integration test passes |
| P7-OBJ-4  | The notified client receives an SMS with a time-limited confirmation link (configurable window, default 15 min)         | Feature test passes                  |
| P7-OBJ-5  | If the client confirms within the window, their reservation is created and the slot is removed from the waitlist        | Feature test passes                  |
| P7-OBJ-6  | If the client does not respond within the window, the next waitlist entry is notified automatically                     | Unit test + feature test passes      |
| P7-OBJ-7  | The business owner can manually trigger "notify next" and reorder or remove entries from the dashboard                  | Feature test passes                  |
| P7-OBJ-8  | Léo (Telegram/WhatsApp) reports waitlist count when asked for pending clients for a slot                               | Unit test passes (updated tool)      |
| P7-OBJ-9  | Backend test coverage ≥ 80%, frontend ≥ 80%                                                                           | CI coverage gate passes              |

---

## Section 2 — Entry Criteria

- Phase 6 exit criteria all validated (WhatsApp channel, credit wallet, CI green)
- `ReservationObserver` stable and dispatching `SendLeoNotificationJob` correctly in production
- Twilio SMS integration operational (Phase 1 prerequisite)
- `CLAUDE.md` updated to reference Phase 7 tasks

---

## Section 3 — Scope — Requirement Traceability

| Requirement Group                       | Source Ref                        | Included?  | Notes                                                                                          |
|-----------------------------------------|-----------------------------------|------------|------------------------------------------------------------------------------------------------|
| Waitlist creation (business owner)      | Leo PRD §6 V2, conversation       | Yes        | Full — manual add from dashboard with name, phone, party size, slot date/time.                |
| Waitlist self-registration (public)     | Conversation                      | Yes        | Public tokenised link per business. Rate limited. No account required.                        |
| Cancellation-triggered notification     | Conversation                      | Yes        | ReservationObserver hook. SMS via Twilio. Time window configurable per business.              |
| WhatsApp notification channel           | Conversation                      | Partial    | SMS only for MVP. WhatsApp channel integration deferred to Phase 8 polish.                    |
| Automatic slot filling + reservation    | Conversation                      | Yes        | Confirmation link creates reservation directly. No manual step from business owner.           |
| Cascade to next entry on timeout        | Conversation                      | Yes        | ExpireWaitlistEntriesCommand runs every minute via scheduler. Notifies next pending entry.    |
| Manual notify / reorder / remove        | Conversation                      | Yes        | Dashboard actions for business owner.                                                         |
| Léo waitlist awareness                  | Conversation                      | Yes        | GetPendingReservationsTool updated to include waitlist count in response.                     |
| Deposit required for waitlist slot      | Conversation                      | No         | Deferred — no Stripe integration for waitlist in this phase.                                  |
| WhatsApp/Telegram self-registration     | Conversation                      | No         | Deferred — public link (SMS confirmation) is sufficient for MVP.                              |

---

## Section 4 — Detailed Sprint Breakdown

### 4.9 Sprint 9 — Waitlist Core Backend (Weeks 15–16)

#### 4.9.1 Sprint Objectives

- `waitlist_entries` table migrated with all required columns and indexes
- `WaitlistEntry` model, `WaitlistPolicy`, `WaitlistEntryResource` delivered
- Business owner CRUD (add, remove, reorder) operational via API
- `NotifyWaitlistJob` dispatched on cancellation, sends SMS confirmation link with 15-min TTL
- `WaitlistConfirmController` handles confirmation and decline links, creates reservation on confirm
- Cascade to next entry on timeout operational via scheduler command

---

#### 4.9.2 Database Migrations

| Migration name                              | Description                                                                                                                                                                                                                                                                                                                                                                                                                                                     |
|---------------------------------------------|-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `create_waitlist_entries_table`             | id UUID PK, business_id UUID FK→businesses(id) ON DELETE CASCADE NOT NULL, slot_date DATE NOT NULL, slot_time TIME NOT NULL, client_name VARCHAR(150) NOT NULL, client_phone VARCHAR(20) NOT NULL (E.164 format), party_size SMALLINT NOT NULL DEFAULT 1, priority_order INT NOT NULL DEFAULT 0 (lower = higher priority; reordered by business owner), status ENUM('pending','notified','confirmed','declined','expired') NOT NULL DEFAULT 'pending', channel ENUM('sms','whatsapp') NOT NULL DEFAULT 'sms', notified_at TIMESTAMPTZ nullable, expires_at TIMESTAMPTZ nullable (notification TTL expiry), confirmed_at TIMESTAMPTZ nullable, confirmation_token VARCHAR(64) UNIQUE nullable (hex token for confirm/decline links), created_at TIMESTAMPTZ, updated_at TIMESTAMPTZ. Indexes: `(business_id, slot_date, status)` composite btree, `expires_at` btree (scheduler lookup), `confirmation_token` btree (webhook lookup). |
| `add_waitlist_config_to_businesses_table`   | Add `waitlist_enabled BOOLEAN NOT NULL DEFAULT false`, `waitlist_notification_window_minutes SMALLINT NOT NULL DEFAULT 15` (how long the client has to respond), `waitlist_public_token VARCHAR(64) UNIQUE nullable` (token for public self-registration URL, generated on first enable). Indexes: `waitlist_enabled` btree.                                                                                                                                    |

---

#### 4.9.3 Back-end Tasks

| ID         | Task                                                                                                                                                                                                                                                                                                                                                                          | PRD Ref       |
|------------|-------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|---------------|
| P7-BE-001  | Create migration `create_waitlist_entries_table` per Section 4.9.2 — implement `up()/down()` with Blueprint.                                                                                                                                                                                                                                                                  | Conversation  |
| P7-BE-002  | Create migration `add_waitlist_config_to_businesses_table` per Section 4.9.2.                                                                                                                                                                                                                                                                                                 | Conversation  |
| P7-BE-003  | Create `app/Models/WaitlistEntry.php` — `HasUuids`; `belongsTo(Business::class)`; cast `status` to `WaitlistStatusEnum`; cast `channel` to `ChannelTypeEnum`; scope `pending(): Builder` filters status=pending; scope `notified(): Builder` filters status=notified; scope `forSlot(string $businessId, string $date, string $time): Builder` filters by business+slot; scope `expiredNotifications(): Builder` filters status=notified AND expires_at < now(); `generateConfirmationToken(): string` generates 64-char hex and sets token on model. | Conversation  |
| P7-BE-004  | Create `app/Enums/WaitlistStatusEnum.php` — cases: `Pending`, `Notified`, `Confirmed`, `Declined`, `Expired`; add `label(): string` method returning French label.                                                                                                                                                                                                            | Conversation  |
| P7-BE-005  | Create `app/Policies/WaitlistPolicy.php` — `viewAny/view/create/update/delete`: authenticated user's business must match `waitlist_entry->business_id`; register in `AuthServiceProvider`.                                                                                                                                                                                   | Conversation  |
| P7-BE-006  | Create `app/Http/Requests/StoreWaitlistEntryRequest.php` — validate: `slot_date` required\|date\|after_or_equal:today, `slot_time` required\|date_format:H:i, `client_name` required\|string\|max:150, `client_phone` required\|string\|regex:/^\+[1-9]\d{7,14}$/ (E.164), `party_size` required\|integer\|min:1\|max:50; French error messages.                              | Conversation  |
| P7-BE-007  | Create `app/Http/Resources/WaitlistEntryResource.php` — expose: id, business_id, slot_date, slot_time, client_name, client_phone (masked: last 4 digits), party_size, status, channel, priority_order, notified_at, expires_at, confirmed_at, created_at; never expose confirmation_token.                                                                                   | Conversation  |
| P7-BE-008  | Create `app/Http/Controllers/Api/WaitlistController.php` — `index(Request $request): JsonResponse` returns paginated `WaitlistEntryResource` for authenticated business, filterable by `?slot_date`, `?status`; `store(StoreWaitlistEntryRequest $request): JsonResponse` creates entry with `priority_order = max(existing) + 1`, returns 201; `destroy(WaitlistEntry $entry): JsonResponse` hard-deletes if status=pending, returns 204; all methods authorised via `WaitlistPolicy`.  | Conversation  |
| P7-BE-009  | Create `app/Http/Controllers/Api/WaitlistController@reorder` — `reorder(Request $request): JsonResponse` accepts `{ordered_ids: string[]}` (array of entry UUIDs for a given slot); validates all IDs belong to authenticated business and same slot; updates `priority_order` in a single transaction using array index; returns updated collection as `WaitlistEntryResource[]`.  | Conversation  |
| P7-BE-010  | Create `app/Http/Controllers/Api/WaitlistController@notify` — `notify(WaitlistEntry $entry): JsonResponse` allows business owner to manually trigger notification for a specific pending entry; authorises via `WaitlistPolicy`; dispatches `NotifyWaitlistJob`; returns 202.                                                                                                 | Conversation  |
| P7-BE-011  | Create `app/Services/WaitlistService.php` — `notifyNext(string $businessId, string $slotDate, string $slotTime): ?WaitlistEntry` finds the lowest `priority_order` entry with status=pending for the slot, generates confirmation token, sets `notified_at=now()`, `expires_at=now()+window_minutes`, status=notified, returns entry or null if queue empty; `confirmSlot(WaitlistEntry $entry): Reservation` creates a new Reservation from waitlist entry data (client_name, client_phone, party_size, slot_date+slot_time as reserved_at), marks entry as confirmed, marks remaining entries for same slot as expired; `declineSlot(WaitlistEntry $entry): void` marks entry as declined, immediately calls `notifyNext()` for the slot; all wrapped in DB transactions. | Conversation  |
| P7-BE-012  | Create `app/Jobs/NotifyWaitlistJob.php` — `handle()`: receives `string $businessId`, `string $slotDate`, `string $slotTime`; calls `WaitlistService::notifyNext()`; if entry found, sends SMS via `SmsServiceInterface` with message "Votre table est disponible chez {businessName} le {date} à {time}. Confirmez dans {window} minutes : {url}"; URL = `{APP_URL}/waitlist/confirm/{token}`; logs to Laravel Log; retries 3× with exponential backoff on SMS failure; if queue is empty, no action.  | Conversation  |
| P7-BE-013  | Create `app/Jobs/ExpireWaitlistNotificationsJob.php` — `handle()`: queries `WaitlistEntry::expiredNotifications()`, chunks by 100; for each expired entry: calls `WaitlistService::declineSlot()` if entry is still notified (idempotent check); dispatches `NotifyWaitlistJob` for the slot to cascade to next entry; designed to run every minute via scheduler.           | Conversation  |
| P7-BE-014  | Create `app/Http/Controllers/Public/WaitlistConfirmController.php` — `confirm(string $token): RedirectResponse` finds WaitlistEntry by token where status=notified AND expires_at > now(); calls `WaitlistService::confirmSlot()`; redirects to `{FRONTEND_URL}/waitlist/confirmed` with query `?name={client_name}&slot={date}T{time}`; if token invalid/expired redirects to `{FRONTEND_URL}/waitlist/expired`; `decline(string $token): RedirectResponse` calls `WaitlistService::declineSlot()`, redirects to `{FRONTEND_URL}/waitlist/declined`.  | Conversation  |
| P7-BE-015  | Update `app/Observers/ReservationObserver.php` — in `updated()`: when status transitions to `cancelled_by_client` or `cancelled_by_business`, check if `business->waitlist_enabled` and if any pending entries exist for the slot (date + time from `reserved_at`); if so, dispatch `NotifyWaitlistJob`; keep existing `SendLeoNotificationJob` dispatch unchanged.          | Conversation  |
| P7-BE-016  | Register `ExpireWaitlistNotificationsJob` in `app/Console/Kernel.php` — run `everyMinute()`; add to `app/Console/Commands/` as `waitlist:expire-notifications` signature wrapper that dispatches the job; document in Audit Notes.                                                                                                                                           | Conversation  |
| P7-BE-017  | Update `app/Leo/Tools/GetPendingReservationsTool.php` — `execute(string $businessId): array` now appends a `waitlist_count` field to each returned slot: count of pending waitlist entries for that date/time; if no waitlist, field is 0; never exposes client phone numbers.                                                                                                | Leo PRD §5.2  |

---

#### 4.9.4 Back-end Tests (TDD)

| Test File                                                     | Test Cases                                                                                                                                                                                                                                                                                                                      |
|---------------------------------------------------------------|---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `tests/Unit/Services/WaitlistServiceTest.php`                | notifyNext returns null when queue is empty, notifyNext returns lowest-priority entry and sets status=notified, notifyNext generates unique confirmation token, notifyNext sets expires_at based on business window_minutes, confirmSlot creates reservation from waitlist data, confirmSlot marks entry as confirmed, confirmSlot marks other same-slot entries as expired, declineSlot marks entry as declined, declineSlot calls notifyNext for the slot |
| `tests/Unit/Jobs/NotifyWaitlistJobTest.php`                  | dispatches SMS with correct confirmation URL, skips if queue is empty after service call, retries on SMS service exception, never includes raw phone in log output                                                                                                                                                               |
| `tests/Unit/Jobs/ExpireWaitlistNotificationsJobTest.php`     | marks expired notified entries as declined, does not touch entries that are not yet expired, calls notifyNext for each expired slot, handles empty result set gracefully                                                                                                                                                         |
| `tests/Feature/Waitlist/WaitlistCrudTest.php`                | index returns entries filtered by slot_date, index returns 401 for unauthenticated, store creates entry with correct priority_order, store returns 422 on invalid phone format, store returns 403 for other business's waitlist, destroy deletes pending entry and returns 204, destroy returns 403 for other business           |
| `tests/Feature/Waitlist/WaitlistReorderTest.php`             | reorder updates priority_order for all IDs in correct sequence, reorder returns 422 when IDs belong to different business, reorder returns 422 when IDs span different slots                                                                                                                                                    |
| `tests/Feature/Waitlist/WaitlistNotifyTest.php`              | notify dispatches NotifyWaitlistJob for pending entry, notify returns 403 for other business's entry, notify returns 422 when entry is not pending                                                                                                                                                                              |
| `tests/Feature/Waitlist/WaitlistConfirmTest.php`             | confirm with valid non-expired token creates reservation and marks entry confirmed, confirm with expired token redirects to /waitlist/expired, confirm with invalid token redirects to /waitlist/expired, decline with valid token marks entry declined and triggers next notification, decline with already-confirmed token is idempotent |
| `tests/Feature/Waitlist/ReservationObserverWaitlistTest.php` | dispatches NotifyWaitlistJob when reservation cancelled and waitlist has pending entries, does not dispatch when waitlist_enabled=false on business, does not dispatch when no pending entries for the slot, does not dispatch when cancellation status is not a cancel type                                                      |

---

#### 4.9.5 Front-end Tasks

| ID         | Task                                                                                                                                                                                                                                                             | PRD Ref      |
|------------|------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|--------------|
| P7-FE-001  | Create `src/api/waitlist.ts` — typed API client: `getWaitlistEntries(params: WaitlistFilter): Promise<PaginatedResponse<WaitlistEntry>>`, `addWaitlistEntry(payload: CreateWaitlistEntryPayload): Promise<WaitlistEntry>`, `removeWaitlistEntry(id: string): Promise<void>`, `reorderWaitlist(slotId: string, orderedIds: string[]): Promise<WaitlistEntry[]>`, `notifyNext(id: string): Promise<void>`; define `WaitlistEntry`, `WaitlistFilter`, `CreateWaitlistEntryPayload` TypeScript interfaces; use existing `axios` instance. | Conversation |
| P7-FE-002  | Create `src/composables/useWaitlist.ts` — `entries: Ref<WaitlistEntry[]>`, `loading: Ref<boolean>`, `error: Ref<string \| null>`, `fetchEntries(filter)`, `addEntry(payload)`, `removeEntry(id)`, `reorder(orderedIds)`, `notifyEntry(id)`; computed: `pendingCount`, `notifiedCount`.  | Conversation |
| P7-FE-003  | Create `src/views/WaitlistView.vue` — page at `/waitlist` route (requiresAuth); header "Liste d'attente" + date picker to filter by slot date; table of entries grouped by time slot; "Ajouter" button opens `AddWaitlistEntryModal`; empty state "Aucune entrée en attente" when list is empty; uses `useWaitlist` composable.  | Conversation |
| P7-FE-004  | Create `src/components/waitlist/WaitlistEntryRow.vue` — table row: client name, masked phone, party size, `WaitlistStatusBadge`, notified_at + expires countdown (live, using `usePolling`), "Notifier" button (shown only for pending entries), "Supprimer" button with confirmation; drag handle for reorder (emit `reorder`); Props: `entry: WaitlistEntry`; Emits: `notified`, `removed`, `reorder`.  | Conversation |
| P7-FE-005  | Create `src/components/waitlist/WaitlistStatusBadge.vue` — pill badge: pending (grey), notified (yellow + countdown), confirmed (green), declined (red), expired (dark grey); Props: `status: WaitlistStatus`, `expiresAt?: string`; shows live countdown "Expire dans 4:32" when status=notified.  | Conversation |
| P7-FE-006  | Create `src/components/waitlist/AddWaitlistEntryModal.vue` — modal form: slot date (date picker), slot time (time picker), client name (text), client phone (E.164 input with flag selector), party size (number input 1–50); client-side validation before submit; emits `created` on success; shows server error on API failure.  | Conversation |
| P7-FE-007  | Add `/waitlist` route to `src/router/index.ts` — `requiresAuth: true`, lazy-loaded `WaitlistView`, add "Liste d'attente" nav link in sidebar with clock-rotate icon.  | Conversation |
| P7-FE-008  | Create `src/views/public/WaitlistConfirmedView.vue` — public, no auth; reads `?name` and `?slot` query params; shows "✅ {name}, votre réservation est confirmée pour le {slot}" with celebration illustration; CTA links to business website (if configured).  | Conversation |
| P7-FE-009  | Create `src/views/public/WaitlistExpiredView.vue` — public, no auth; shows "⏰ Ce lien a expiré" with friendly explanation; no CTA.  | Conversation |
| P7-FE-010  | Create `src/views/public/WaitlistDeclinedView.vue` — public, no auth; shows "👋 Pas de souci, nous avons prévenu le prochain client."; no CTA.  | Conversation |

---

#### 4.9.6 Front-end Tests

| Test File                                                              | Test Cases                                                                                                                                                                              |
|------------------------------------------------------------------------|-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `src/composables/__tests__/useWaitlist.test.ts`                       | fetchEntries populates entries on success, addEntry appends to list on success, removeEntry removes from list, notifyEntry calls API and shows success toast, error is set on API failure |
| `src/components/waitlist/__tests__/WaitlistStatusBadge.test.ts`       | renders correct label and colour for each status, shows countdown when status=notified and expiresAt provided, hides countdown when status is not notified                              |
| `src/components/waitlist/__tests__/AddWaitlistEntryModal.test.ts`     | shows validation error for invalid phone format, shows validation error for past slot_date, submits with valid data and emits created, shows server error on API failure               |
| `src/views/public/__tests__/WaitlistConfirmedView.test.ts`            | renders client name and slot from query params, renders fallback when params missing                                                                                                   |

---

#### 4.9.7 DevOps / Infrastructure Tasks

| ID         | Task                                                                                                                                                     | PRD Ref      |
|------------|----------------------------------------------------------------------------------------------------------------------------------------------------------|--------------|
| P7-DO-001  | Add waitlist API routes to `routes/api.php` — group `/api/v1/waitlist` protected by `auth:sanctum`; add public routes `/waitlist/confirm/{token}` and `/waitlist/decline/{token}` in `routes/web.php` (redirect-based flow). | Conversation |
| P7-DO-002  | Add public waitlist confirmation routes to `routes/web.php` — GET `/waitlist/confirm/{token}` and `/waitlist/decline/{token}` → `WaitlistConfirmController`; no auth middleware; rate limit: 10/minute per IP. | Conversation |

---

#### 4.9.8 Deliverables Checklist

- [ ] `waitlist_entries` and businesses config columns migrated and verified
- [ ] `WaitlistEntry` model with all scopes and token generation tested
- [ ] `WaitlistService` (notifyNext, confirmSlot, declineSlot) fully tested
- [ ] `NotifyWaitlistJob` sends SMS with correct confirmation URL
- [ ] `ExpireWaitlistNotificationsJob` cascades to next entry on timeout
- [ ] `ReservationObserver` dispatches `NotifyWaitlistJob` on cancellation
- [ ] `WaitlistConfirmController` creates reservation on confirm, triggers cascade on decline
- [ ] Dashboard CRUD (add, remove, reorder, manual notify) operational
- [ ] Public confirm/decline/expired views rendered correctly
- [ ] All Sprint 9 backend + frontend tests passing

---

### 4.10 Sprint 10 — Waitlist Public Registration & Polish (Weeks 17–18)

#### 4.10.1 Sprint Objectives

- Public self-registration link operational (`/join/{publicToken}`)
- Business owner can enable/disable waitlist and configure notification window per establishment
- WhatsApp notification channel added for waitlist notifications (uses existing Phase 6 credit system)
- Léo correctly reports waitlist count in pending reservations tool
- Full CI green with coverage ≥ 80%

---

#### 4.10.2 Database Migrations

No new migrations in Sprint 10. All schema changes delivered in Sprint 9.

---

#### 4.10.3 Back-end Tasks

| ID         | Task                                                                                                                                                                                                                                                                                                                  | PRD Ref      |
|------------|-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|--------------|
| P7-BE-020  | Create `app/Http/Controllers/Public/PublicWaitlistController.php` — `show(string $publicToken): JsonResponse` finds business by `waitlist_public_token`, returns `{business_name, slots_available: [{date, time, existing_count}]}`; `store(PublicStoreWaitlistRequest $request, string $publicToken): JsonResponse` creates `WaitlistEntry` with `priority_order = max + 1` for the requested slot; rate-limited to 5 requests/minute per IP; returns 201 `WaitlistEntryResource` (no phone masking for own entry). | Conversation |
| P7-BE-021  | Create `app/Http/Requests/PublicStoreWaitlistRequest.php` — validate: `slot_date` required\|date\|after_or_equal:today, `slot_time` required\|date_format:H:i, `client_name` required\|string\|max:150, `client_phone` required\|string\|regex:E.164, `party_size` required\|integer\|min:1\|max:50; French messages. | Conversation |
| P7-BE-022  | Create `app/Services/WaitlistPublicLinkService.php` — `generateToken(Business $business): string` generates 32-char hex token, stores in `business->waitlist_public_token`, returns the full public URL `{APP_URL}/join/{token}`; `invalidateToken(Business $business): void` nulls the token (old links stop working). | Conversation |
| P7-BE-023  | Create `app/Http/Controllers/Api/WaitlistSettingsController.php` — `show(Request $request): JsonResponse` returns `{waitlist_enabled, waitlist_notification_window_minutes, public_registration_url}`; `update(WaitlistSettingsRequest $request): JsonResponse` updates `waitlist_enabled` and `waitlist_notification_window_minutes` on authenticated business; `regenerateLink(Request $request): JsonResponse` calls `WaitlistPublicLinkService::generateToken()`, returns new URL. | Conversation |
| P7-BE-024  | Create `app/Http/Requests/WaitlistSettingsRequest.php` — validate: `waitlist_enabled` required\|boolean, `waitlist_notification_window_minutes` required\|integer\|min:5\|max:60; French messages. | Conversation |
| P7-BE-025  | Update `app/Jobs/NotifyWaitlistJob.php` — check `WaitlistEntry->channel`; if 'whatsapp', send via `WhatsAppChannel::sendMessage()` using business's Leo WhatsApp channel (if active and credit available via `LeoWhatsAppCreditService::hasSufficientCredit()`); fallback to SMS if WhatsApp unavailable; deduct WhatsApp credit on success. | Conversation |
| P7-BE-026  | Create `app/Console/Commands/RegenerateWaitlistStats.php` — `waitlist:stats` outputs per-business stats: total entries this month, confirmed count, expired count, average confirmation time; used for internal monitoring; not scheduled. | Conversation |

---

#### 4.10.4 Back-end Tests (TDD)

| Test File                                                         | Test Cases                                                                                                                                                                                                      |
|-------------------------------------------------------------------|-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `tests/Feature/Waitlist/PublicWaitlistRegistrationTest.php`      | show returns business name and available slots for valid token, show returns 404 for invalid token, store creates waitlist entry for valid token and slot, store returns 429 when rate limit exceeded, store returns 422 on invalid phone, store returns 404 for unknown token |
| `tests/Feature/Waitlist/WaitlistSettingsTest.php`                | show returns current settings and public URL, update enables waitlist and sets window, update returns 422 when window < 5 or > 60, regenerateLink returns new public URL, old public token is invalidated after regenerate |
| `tests/Unit/Services/WaitlistPublicLinkServiceTest.php`          | generateToken returns URL with 32-char hex token, generateToken stores token on business, invalidateToken nulls the token on business                                                                            |

---

#### 4.10.5 Front-end Tasks

| ID         | Task                                                                                                                                                                                                                                                                                                  | PRD Ref      |
|------------|-------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|--------------|
| P7-FE-020  | Create `src/views/public/PublicWaitlistView.vue` — public, no auth, at `/join/:token`; fetches business name and available slots via `PublicWaitlistController@show`; form: slot selector (date + time dropdown populated from available slots), client name, phone (E.164), party size; submit → POST → shows "✅ Vous êtes sur la liste d'attente. Nous vous contacterons par SMS si une place se libère."; handles 404 (unknown link) and 422 (validation errors). | Conversation |
| P7-FE-021  | Add `/join/:token` route to `src/router/index.ts` — `requiresAuth: false`, lazy-loaded `PublicWaitlistView`. | Conversation |
| P7-FE-022  | Create `src/components/waitlist/WaitlistSettingsCard.vue` — card in WaitlistView: waitlist enabled toggle; notification window slider (5–60 min, step 5); public registration link display with copy button; "Régénérer le lien" button (with confirmation warning "L'ancien lien ne fonctionnera plus"); saves on change via `WaitlistSettingsController`; Props: none (uses composable). | Conversation |
| P7-FE-023  | Update `src/api/waitlist.ts` — add `getWaitlistSettings(): Promise<WaitlistSettings>`, `updateWaitlistSettings(payload): Promise<WaitlistSettings>`, `regeneratePublicLink(): Promise<{public_registration_url: string}>`; add `WaitlistSettings` interface. | Conversation |

---

#### 4.10.6 Front-end Tests

| Test File                                                          | Test Cases                                                                                                                                               |
|--------------------------------------------------------------------|----------------------------------------------------------------------------------------------------------------------------------------------------------|
| `src/views/public/__tests__/PublicWaitlistView.test.ts`           | renders business name and slot options from API, submits form and shows success message, shows validation error on invalid phone, shows 404 state for unknown token |
| `src/components/waitlist/__tests__/WaitlistSettingsCard.test.ts`  | renders enabled toggle and window slider, copy button copies URL to clipboard, regenerate link shows confirmation dialog, confirm regenerate calls API and updates displayed URL |

---

#### 4.10.7 DevOps / Infrastructure Tasks

| ID         | Task                                                                                                                              | PRD Ref      |
|------------|-----------------------------------------------------------------------------------------------------------------------------------|--------------|
| P7-DO-020  | Add public `/join/{publicToken}` route to `routes/web.php` → `PublicWaitlistController@show` (GET) and `@store` (POST); rate limit `5/minute` per IP via `ThrottleRequests` middleware. | Conversation |
| P7-DO-021  | Add waitlist settings routes to `routes/api.php` — `GET /api/v1/waitlist/settings`, `PATCH /api/v1/waitlist/settings`, `POST /api/v1/waitlist/settings/regenerate-link`; protected by `auth:sanctum`. | Conversation |

---

#### 4.10.8 Deliverables Checklist

- [ ] Public self-registration page live at `/join/{token}` with slot selector and confirmation message
- [ ] `WaitlistSettingsCard` allows enabling waitlist, configuring window, and sharing/regenerating link
- [ ] WhatsApp fallback in `NotifyWaitlistJob` uses Phase 6 credit system
- [ ] Léo `GetPendingReservationsTool` returns `waitlist_count` per slot
- [ ] All Sprint 10 backend + frontend tests passing
- [ ] Backend coverage ≥ 80%, frontend ≥ 80%
- [ ] CI pipeline green on `main`

---

## Section 5 — API Endpoints Delivered in Phase 7

| Method | Endpoint                                      | Controller                              | Auth    | Notes                                                                                                                      |
|--------|-----------------------------------------------|-----------------------------------------|---------|----------------------------------------------------------------------------------------------------------------------------|
| GET    | `/api/v1/waitlist`                            | `WaitlistController@index`              | Bearer  | Returns paginated `WaitlistEntryResource[]`. Accepts `?slot_date`, `?status`.                                             |
| POST   | `/api/v1/waitlist`                            | `WaitlistController@store`              | Bearer  | Body: `{slot_date, slot_time, client_name, client_phone, party_size}`. Returns `WaitlistEntryResource` 201.               |
| DELETE | `/api/v1/waitlist/{id}`                       | `WaitlistController@destroy`            | Bearer  | Returns 204. 403 if not owned by business. 422 if entry is not pending.                                                   |
| POST   | `/api/v1/waitlist/reorder`                    | `WaitlistController@reorder`            | Bearer  | Body: `{ordered_ids: string[]}`. Returns updated `WaitlistEntryResource[]`.                                               |
| POST   | `/api/v1/waitlist/{id}/notify`               | `WaitlistController@notify`             | Bearer  | Manually triggers notification for pending entry. Returns 202.                                                             |
| GET    | `/api/v1/waitlist/settings`                   | `WaitlistSettingsController@show`       | Bearer  | Returns `{waitlist_enabled, waitlist_notification_window_minutes, public_registration_url}`.                               |
| PATCH  | `/api/v1/waitlist/settings`                   | `WaitlistSettingsController@update`     | Bearer  | Body: `{waitlist_enabled, waitlist_notification_window_minutes}`. Returns updated settings.                                |
| POST   | `/api/v1/waitlist/settings/regenerate-link`  | `WaitlistSettingsController@regenerateLink` | Bearer | Returns `{public_registration_url: string}`. Invalidates old token.                                                   |
| GET    | `/join/{publicToken}`                         | `PublicWaitlistController@show`         | No      | Returns `{business_name, slots}`. 404 if token unknown or waitlist disabled.                                              |
| POST   | `/join/{publicToken}`                         | `PublicWaitlistController@store`        | No      | Body: `{slot_date, slot_time, client_name, client_phone, party_size}`. Rate limited 5/min per IP. Returns 201.            |
| GET    | `/waitlist/confirm/{token}`                   | `WaitlistConfirmController@confirm`     | No      | Confirms slot. Redirects to `/waitlist/confirmed` or `/waitlist/expired`.                                                 |
| GET    | `/waitlist/decline/{token}`                   | `WaitlistConfirmController@decline`     | No      | Declines slot. Redirects to `/waitlist/declined`. Triggers next notification.                                              |

---

## Section 6 — Exit Criteria

| #  | Criterion                                                                                                    | Validated |
|----|--------------------------------------------------------------------------------------------------------------|-----------|
| 1  | All P7 functional requirements implemented: waitlist CRUD, notification, confirmation, cascade, public link  | [ ]       |
| 2  | Backend test coverage ≥ 80%                                                                                  | [ ]       |
| 3  | Frontend test coverage ≥ 80%                                                                                 | [ ]       |
| 4  | Pint passes with zero errors                                                                                  | [ ]       |
| 5  | PHPStan level 8 passes with zero errors                                                                      | [ ]       |
| 6  | ESLint + Prettier passes with zero errors                                                                     | [ ]       |
| 7  | All Pest tests pass                                                                                           | [ ]       |
| 8  | All Vitest tests pass                                                                                         | [ ]       |
| 9  | CI pipeline green on `main`                                                                                   | [ ]       |
| 10 | End-to-end test in staging: cancel a reservation → SMS received → confirmed → reservation created            | [ ]       |
| 11 | Cascade test in staging: no response within window → next entry notified                                     | [ ]       |
| 12 | Public link test: client self-registers → appears in dashboard → receives SMS on cancellation                | [ ]       |
| 13 | `docs/dev/phase7.md` fully updated with all tasks marked `done` or `merged`                                  | [ ]       |

---

## Section 7 — Risks Specific to Phase 7

| Risk                                                       | Probability | Impact | Mitigation                                                                                                                           |
|------------------------------------------------------------|-------------|--------|--------------------------------------------------------------------------------------------------------------------------------------|
| ExpireWaitlistNotificationsJob fires too frequently        | Medium      | Low    | Idempotent check (status=notified) prevents double-cascade; scheduler runs everyMinute but job is fast.                             |
| Client receives SMS but slot already filled by phone call  | Medium      | Medium | Business owner can manually mark slot as unavailable; UI shows current waitlist count prominently.                                   |
| Twilio SMS rate limits hit during burst cancellations      | Low         | High   | Job queued on `default` queue; Twilio rate limits are per number (many per second); stagger via Horizon.                            |
| Public link scraped and abused (spam registrations)        | Low         | Medium | Rate limit 5/min per IP on public endpoint; business owner can regenerate token instantly.                                           |
| ReservationObserver already dispatches Leo notifications   | Low         | Medium | Both jobs dispatched independently; verified in tests to not interfere.                                                              |

---

## Section 8 — External Dependencies

| Service / Library | Phase 7 Usage                                              | Fallback if Unavailable                                    |
|-------------------|------------------------------------------------------------|------------------------------------------------------------|
| Twilio SMS        | Waitlist confirmation SMS (same service as existing reminders) | Log SMS content; business owner notifies manually      |
| WhatsApp Channel  | Optional WhatsApp notification (Phase 6 credit system)     | Falls back to SMS automatically if WhatsApp unavailable   |
| Laravel Scheduler | `ExpireWaitlistNotificationsJob` every minute              | Manual Artisan `waitlist:expire-notifications` invocation |

---

## Assumptions

> The following assumptions were made during spec generation. Review and adjust before implementation begins.

- A "slot" is defined by `(business_id, slot_date, slot_time)`. No reservation ID is required — the waitlist is slot-based, not reservation-based. This allows multiple waitlist entries to compete for the same time slot freed by any cancellation.
- Confirmation creates a brand-new Reservation record (not a reassignment of the cancelled one). The cancelled reservation stays cancelled for audit purposes.
- The public registration link is per-business (not per-slot). Clients select their preferred slot from a list shown on the public page.
- A business may only have one active public token at a time. Regenerating the token invalidates the old one immediately.
- The WhatsApp notification channel for waitlist uses the same `LeoChannel` and `LeoWhatsAppCreditService` from Phase 6 — no separate billing setup required.
