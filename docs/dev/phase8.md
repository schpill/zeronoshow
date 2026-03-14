# Phase 8 — Task Tracking

> **Status**: Implemented
> **Spec**: [docs/phases/phase8.md](../phases/phase8.md)
> **Last audit**: 2026-03-14

---

## Sprint 11 — Voice Call Backend & TwiML (Weeks 19–20)

### Backend

| ID         | Task                                                                         | Status | Owner |
|------------|------------------------------------------------------------------------------|--------|-------|
| P8-BE-001  | Migration: create_voice_call_logs_table                                      | done   | Gerald |
| P8-BE-002  | Migration: add_voice_credits_to_businesses_table                             | done   | Gerald |
| P8-BE-003  | Create VoiceCallLog model (HasUuids, scopes, belongs to reservation)         | done   | Gerald |
| P8-BE-004  | Create VoiceCreditService (deduct, topUp, suspend, hasSufficient)            | done   | Gerald |
| P8-BE-005  | Create VoiceCallService (placeCall, retry logic, credit check)               | done   | Gerald |
| P8-BE-006  | Create PlaceVoiceCallJob (Twilio Voice outbound dispatch, retry 2x)          | done   | Gerald |
| P8-BE-007  | Create VoiceTwimlController (GET — returns French TwiML with Polly.Léa)     | done   | Gerald |
| P8-BE-008  | Create VoiceGatherController (POST — handle DTMF: 1=confirm, 2=cancel)      | done   | Gerald |
| P8-BE-009  | Create VoiceStatusController (POST — Twilio status callback, update log)     | done   | Gerald |
| P8-BE-010  | Create VoiceCreditController (status, topup, setCap)                         | done   | Gerald |
| P8-BE-011  | Create TopUpVoiceRequest + SetVoiceCapRequest form requests                  | done   | Gerald |
| P8-BE-012  | Create VoiceCreditResource                                                   | done   | Gerald |
| P8-BE-013  | Add voice routes to routes/api.php (TwiML + status callbacks + credit API)  | done   | Gerald |
| P8-BE-014  | Register PlaceVoiceCallJob trigger in reminder scheduling pipeline           | done   | Gerald |
| P8-BE-015  | Create LeoVoiceCreditExhaustedEvent + LeoVoiceLowBalanceEvent               | done   | Gerald |
| P8-BE-016  | Create credit alert listeners + VoiceCreditExhaustedMail + LowBalanceMail   | done   | Gerald |
| P8-BE-017  | Add voice cost config to config/leo.php                                      | done   | Gerald |
| P8-BE-018  | Register VoiceChannel in AppServiceProvider                                  | done   | Gerald |

### Frontend

| ID         | Task                                                                         | Status | Owner |
|------------|------------------------------------------------------------------------------|--------|-------|
| P8-FE-001  | Create src/api/voiceCredits.ts (typed client + VoiceCreditStatus type)      | done   | Gerald |
| P8-FE-002  | Create src/composables/useVoiceCredits.ts (fetch, topUp, setCap, computed)  | done   | Gerald |
| P8-FE-003  | Create VoiceCreditCard.vue (balance bar, low-balance warning, top-up CTA)   | done   | Gerald |
| P8-FE-004  | Create VoiceTopUpModal.vue (preset buttons + custom input + Stripe CTA)     | done   | Gerald |
| P8-FE-005  | Create VoiceCapEditForm.vue (inline budget + auto-renew edit)               | done   | Gerald |
| P8-FE-006  | Update LeoView.vue — mount VoiceCreditCard for Voice channel type           | done   | Gerald |
| P8-FE-007  | Enable Voice in CreateLeoChannelForm.vue + inline budget setup section      | done   | Gerald |
| P8-FE-008  | Create VoiceReturnView.vue + /leo/voice/topup/return route                  | done   | Gerald |
| P8-FE-009  | Add route /leo/voice/topup/return to router/index.ts                        | done   | Gerald |
| P8-FE-010  | Create VoiceCallLogView.vue (list of calls with status, duration, outcome)  | done   | Gerald |

### DevOps

| ID         | Task                                                                         | Status | Owner |
|------------|------------------------------------------------------------------------------|--------|-------|
| P8-DO-001  | Add TWILIO_VOICE_* env vars to .env.example + config/services.php           | done   | Gerald |
| P8-DO-002  | Add GET+POST /webhooks/leo/voice/* routes to routes/api.php                 | done   | Gerald |

---

## Sprint 12 — Voice Credit Billing & Dashboard (Weeks 21–22)

### Backend

| ID         | Task                                                                         | Status | Owner |
|------------|------------------------------------------------------------------------------|--------|-------|
| P8-BE-020  | Update StripeWebhookController for voice credit checkout.session.completed   | done   | Gerald |
| P8-BE-021  | Create RenewVoiceCreditJob (Stripe Invoice Item + topUp + mail)             | done   | Gerald |
| P8-BE-022  | Create leo:renew-voice-credits Artisan command (monthly scheduler)           | done   | Gerald |
| P8-BE-023  | Create VoiceCreditRenewedMail Mailable                                       | done   | Gerald |
| P8-BE-024  | Update LeoChannelController::store() — 422 when Voice + cap = 0             | done   | Gerald |
| P8-BE-025  | Create VoiceCallStatsResource (calls placed, answered, confirmed, cancelled) | done   | Gerald |
| P8-BE-026  | Add stats endpoint to VoiceCreditController                                  | done   | Gerald |

### Frontend

| ID         | Task                                                                         | Status | Owner |
|------------|------------------------------------------------------------------------------|--------|-------|
| P8-FE-020  | Create VoiceStatsCard.vue (calls placed, answered rate, confirmation rate)  | done   | Gerald |
| P8-FE-021  | Update LandingView.vue Pricing section (Voice prepaid mention)              | done   | Gerald |
| P8-FE-022  | Add Voice call log link to reservation detail panel                         | done   | Gerald |

### DevOps

| ID         | Task                                                                         | Status | Owner |
|------------|------------------------------------------------------------------------------|--------|-------|
| P8-DO-020  | Update GitHub Actions CI with fake TWILIO_VOICE_* env vars                  | done   | Gerald |
| P8-DO-021  | Document checkout.session.completed voice credit event in Stripe webhook    | done   | Gerald |
| P8-DO-022  | Document Twilio Voice number setup + TwiML app config in Audit Notes        | done   | Gerald |

---

## Audit Notes

| Date       | Note                                                                              |
|------------|-----------------------------------------------------------------------------------|
| 2026-03-14 | Initial generation — Phase 8 Voice Calls spec based on brainstorm 2026-03-14     |
| 2026-03-14 | Key decisions: French TwiML with Amazon Polly Léa voice; prepaid credit wallet mirrors WhatsApp pattern; DTMF gather 1=confirm 2=cancel; retry on no-answer (max 2 retries) |
