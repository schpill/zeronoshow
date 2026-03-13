# Phase 5 — Task Tracking

> **Status**: Implemented
> **Spec**: [docs/phases/phase5.md](../phases/phase5.md)
> **Last audit**: 2026-03-13

---

## Sprint 5 — Léo Core Backend (Weeks 7–8)

### Backend

| ID         | Task                                                                         | Status | Owner |
|------------|------------------------------------------------------------------------------|--------|-------|
| P5-BE-001  | Create LeoChannelInterface contract (3 methods)                              | done   | Codex |
| P5-BE-002  | Create LeoInboundMessage DTO (readonly, PHP 8.3)                             | done   | Codex |
| P5-BE-003  | Create TelegramChannel implementing LeoChannelInterface                      | done   | Codex |
| P5-BE-004  | Create TwilioSmsChannel stub (extension point, not implemented)              | done   | Codex |
| P5-BE-005  | Register channel implementations in AppServiceProvider                       | done   | Codex |
| P5-BE-006  | Migration: create_leo_channels_table                                         | done   | Codex |
| P5-BE-007  | Migration: create_leo_sessions_table                                         | done   | Codex |
| P5-BE-008  | Migration: create_leo_message_logs_table                                     | done   | Codex |
| P5-BE-009  | Create LeoChannel model (HasUuids, casts, scopes)                            | done   | Codex |
| P5-BE-010  | Create LeoSession model (HasUuids, scopes forSender + valid)                 | done   | Codex |
| P5-BE-011  | Create LeoMessageLog model (HasUuids, insert-only)                           | done   | Codex |
| P5-BE-012  | Create LeoSessionService (resolve, set, clear)                               | done   | Codex |
| P5-BE-013  | Create LeoBusinessResolver (NONE/SINGLE/MULTIPLE resolution)                 | done   | Codex |
| P5-BE-014  | Create GetTodayStatsTool (counts by status, score avg)                       | done   | Codex |
| P5-BE-015  | Create GetPendingReservationsTool (no phone numbers)                         | done   | Codex |
| P5-BE-016  | Create GetUpcomingReservationsTool (limit param)                             | done   | Codex |
| P5-BE-017  | Create GetCancelledReservationsTool (both cancel statuses)                   | done   | Codex |
| P5-BE-018  | Create GetReservationDetailsTool (name/time search)                          | done   | Codex |
| P5-BE-019  | Create LeoGeminiService (Gemini 2.5 Flash function calling, 2-step conv.)    | done   | Codex |
| P5-BE-020  | Create LeoWebhookController::telegram (NONE/SINGLE/MULTIPLE handling)        | done   | Codex |

### Frontend

| ID         | Task                                                                         | Status | Owner |
|------------|------------------------------------------------------------------------------|--------|-------|
| P5-FE-001  | Create src/api/leo.ts (typed API client, LeoChannel interface)               | done   | Codex |
| P5-FE-002  | Create useLeoChannels composable (fetch, create, update, delete)             | done   | Codex |
| P5-FE-003  | Create LeoChannelCard.vue (toggle, rename, delete with confirm)              | done   | Codex |
| P5-FE-004  | Create CreateLeoChannelForm.vue (validation, channel type select)            | done   | Codex |
| P5-FE-005  | Create LeoView.vue (/leo route, empty state, modal form)                     | done   | Codex |
| P5-FE-006  | Add /leo route to router (requiresAuth, sidebar nav link)                    | done   | Codex |

### DevOps

| ID         | Task                                                                         | Status | Owner |
|------------|------------------------------------------------------------------------------|--------|-------|
| P5-DO-001  | Add TELEGRAM_BOT_TOKEN, TELEGRAM_WEBHOOK_SECRET, GEMINI_API_KEY to env      | done   | Codex |
| P5-DO-002  | Configure Gemini REST API via Laravel Http client (no extra package needed)  | done   | Codex |
| P5-DO-003  | Add POST /webhooks/leo/telegram route (no auth, IP allowlist middleware)     | done   | Codex |

---

## Sprint 6 — Notifications, Billing & Polish (Weeks 9–10)

### Backend

| ID         | Task                                                                         | Status | Owner |
|------------|------------------------------------------------------------------------------|--------|-------|
| P5-BE-030  | Create SendLeoNotificationJob (cancellation + no_show, throttle check)       | done   | Codex |
| P5-BE-031  | Update ReservationObserver to dispatch SendLeoNotificationJob                | done   | Codex |
| P5-BE-032  | Create LeoMultiBusinessSelectionService (prompt + parse selection)           | done   | Codex |
| P5-BE-033  | Update LeoWebhookController for multi-business selection flow                | done   | Codex |
| P5-BE-034  | Update LeoGeminiService to personalise system prompt with bot/establishment  | done   | Codex |
| P5-BE-035  | Create LeoAddonActiveMiddleware (402 when addon inactive)                    | done   | Codex |
| P5-BE-036  | Create LeoChannelController (one channel/business, 409 on duplicate, CRUD)  | done   | Codex |
| P5-BE-037  | Create StoreLeoChannelRequest (validation, French errors, 409 on duplicate)  | done   | Codex |
| P5-BE-038  | Create UpdateLeoChannelRequest (bot_name, is_active only — no channel change)| done   | Codex |
| P5-BE-039  | Create LeoChannelResource (external_identifier masked, no tokens)            | done   | Codex |
| P5-BE-040  | Create PurgeLeoMessageLogs command (leo-logs:purge, monthly scheduler)       | done   | Codex |
| P5-BE-041  | Create LeoThrottleService (20 msg/hour Redis counter, anti-loop guard)       | done   | Codex |
| P5-BE-042  | Update SendLeoNotificationJob to check LeoThrottleService before sending     | done   | Codex |
| P5-BE-043  | Create LeoAddonSeeder (Stripe product + 9€/month price, idempotent)          | done   | Codex |
| P5-BE-044  | Create LeoAddonController (activate via SubscriptionItem, deactivate, status)| done   | Codex |
| P5-BE-045  | Update StripeWebhookController for Leo add-on failsafe sync                  | done   | Codex |
| P5-BE-046  | Create leo:setup-telegram-webhook Artisan command (deleteWebhook + setWebhook)| done  | Codex |

### Frontend

| ID         | Task                                                                         | Status | Owner |
|------------|------------------------------------------------------------------------------|--------|-------|
| P5-FE-030  | Refactor router/index.ts (/ → LandingView, /dashboard, guestOnly fix)       | done   | Codex |
| P5-FE-031  | Create LandingView.vue (pixel-perfect from template_site_vitrine.html)       | done   | Codex |
| P5-FE-032  | Add Léo add-on card to LandingView.vue Pricing section                       | done   | Codex |
| P5-FE-033  | Create LeoUpgradeBanner.vue (activate CTA, spinner, brand palette)           | done   | Codex |
| P5-FE-034  | Update LeoView.vue (single channel, upgrade banner, empty state)             | done   | Codex |
| P5-FE-035  | Create LeoChannelTypeBadge.vue (Telegram enabled, others "Bientôt")          | done   | Codex |
| P5-FE-036  | Update src/api/leo.ts (activateLeoAddon, deactivateLeoAddon, getAddonStatus) | done   | Codex |
| P5-FE-037  | Update CreateLeoChannelForm.vue (radio channel, Chat ID accordion, warning)  | done   | Codex |
| P5-FE-038  | Update LeoChannelCard.vue ("Changer de canal" with explicit delete warning)  | done   | Codex |
| P5-FE-039  | Add Léo activity widget to Dashboard.vue (last 3 messages, hidden if inactive)| done  | Codex |

### DevOps

| ID         | Task                                                                         | Status | Owner |
|------------|------------------------------------------------------------------------------|--------|-------|
| P5-DO-020  | Add SendLeoNotificationJob to queue worker config                            | done   | Codex |
| P5-DO-021  | Add Leo API route group to routes/api.php                                    | done   | Codex |
| P5-DO-022  | Document Telegram bot creation steps (BotFather flow) in Audit Notes         | done   | Codex |
| P5-DO-023  | Update GitHub Actions CI with fake TELEGRAM_BOT_TOKEN + GEMINI_API_KEY      | done   | Codex |

---

## Audit Notes

| Date       | Note                                                                                                      |
|------------|-----------------------------------------------------------------------------------------------------------|
| 2026-03-13 | Initial generation — Phase 5 Léo spec based on leo_expression_de_besoins.md + architecture decisions    |
| 2026-03-13 | Key decisions: Telegram-first (no WhatsApp MVP), Gemini 2.5 Flash via GEMINI_API_KEY, channel abstraction, 9€/mois Stripe Subscription Item, **un seul canal par établissement** (UNIQUE business_id), changement de canal = delete+create, throttle Redis 20 msg/heure (anti-loop, pas billing), WhatsApp pricing TBD (coût ~0,03€/msg incompatible avec 9€ flat), site vitrine ajouté + refactor routes /→LandingView /dashboard→Dashboard |
| 2026-03-13 | Implémentation APEX livrée: backend Léo (canaux, sessions, logs, webhook Telegram, Gemini fallback, add-on billing, throttle, purge logs) + frontend Léo/dashboard + landing page publique |
| 2026-03-13 | BotFather flow documenté: créer le bot via `/newbot`, renseigner `TELEGRAM_BOT_TOKEN`, définir `TELEGRAM_WEBHOOK_SECRET`, puis exécuter `php artisan leo:setup-telegram-webhook` |
| 2026-03-13 | Sessions Léo conservées en base volontairement pour auditabilité et simplicité opérationnelle; purge opportuniste des sessions expirées à chaque résolution/écriture pour éviter l’accumulation sans dépendre de Redis TTL |

### Telegram Bot Setup (run once at deploy)

```bash
# 1. Create bot via BotFather on Telegram
#    /newbot → choose name → copy token to TELEGRAM_BOT_TOKEN

# 2. Register webhook
php artisan leo:setup-telegram-webhook

# 3. Verify webhook is active
curl https://api.telegram.org/bot{TOKEN}/getWebhookInfo
```
