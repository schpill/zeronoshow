# Phase 8 — Task Tracking

> **Status**: Not started
> **Spec**: [docs/phases/phase8.md](../phases/phase8.md)
> **Last audit**: 2026-03-14

---

## Sprint 11 — Voice Call Backend & TwiML (Weeks 19–20)

### Backend

| ID         | Task                                                                         | Status | Owner |
|------------|------------------------------------------------------------------------------|--------|-------|
| P8-BE-001  | Migration: create_voice_call_logs_table                                      | todo   | —     |
| P8-BE-002  | Migration: add_voice_credits_to_businesses_table                             | todo   | —     |
| P8-BE-003  | Create VoiceCallLog model (HasUuids, scopes, belongs to reservation)         | todo   | —     |
| P8-BE-004  | Create VoiceCreditService (deduct, topUp, suspend, hasSufficient)            | todo   | —     |
| P8-BE-005  | Create VoiceCallService (placeCall, retry logic, credit check)               | todo   | —     |
| P8-BE-006  | Create PlaceVoiceCallJob (Twilio Voice outbound dispatch, retry 2x)          | todo   | —     |
| P8-BE-007  | Create VoiceTwimlController (GET — returns French TwiML with Polly.Léa)     | todo   | —     |
| P8-BE-008  | Create VoiceGatherController (POST — handle DTMF: 1=confirm, 2=cancel)      | todo   | —     |
| P8-BE-009  | Create VoiceStatusController (POST — Twilio status callback, update log)     | todo   | —     |
| P8-BE-010  | Create VoiceCreditController (status, topup, setCap)                         | todo   | —     |
| P8-BE-011  | Create TopUpVoiceRequest + SetVoiceCapRequest form requests                  | todo   | —     |
| P8-BE-012  | Create VoiceCreditResource                                                   | todo   | —     |
| P8-BE-013  | Add voice routes to routes/api.php (TwiML + status callbacks + credit API)  | todo   | —     |
| P8-BE-014  | Register PlaceVoiceCallJob trigger in reminder scheduling pipeline           | todo   | —     |
| P8-BE-015  | Create LeoVoiceCreditExhaustedEvent + LeoVoiceLowBalanceEvent               | todo   | —     |
| P8-BE-016  | Create credit alert listeners + VoiceCreditExhaustedMail + LowBalanceMail   | todo   | —     |
| P8-BE-017  | Add voice cost config to config/leo.php                                      | todo   | —     |
| P8-BE-018  | Register VoiceChannel in AppServiceProvider                                  | todo   | —     |

### Frontend

| ID         | Task                                                                         | Status | Owner |
|------------|------------------------------------------------------------------------------|--------|-------|
| P8-FE-001  | Create src/api/voiceCredits.ts (typed client + VoiceCreditStatus type)      | todo   | —     |
| P8-FE-002  | Create src/composables/useVoiceCredits.ts (fetch, topUp, setCap, computed)  | todo   | —     |
| P8-FE-003  | Create VoiceCreditCard.vue (balance bar, low-balance warning, top-up CTA)   | todo   | —     |
| P8-FE-004  | Create VoiceTopUpModal.vue (preset buttons + custom input + Stripe CTA)     | todo   | —     |
| P8-FE-005  | Create VoiceCapEditForm.vue (inline budget + auto-renew edit)               | todo   | —     |
| P8-FE-006  | Update LeoView.vue — mount VoiceCreditCard for Voice channel type           | todo   | —     |
| P8-FE-007  | Enable Voice in CreateLeoChannelForm.vue + inline budget setup section      | todo   | —     |
| P8-FE-008  | Create VoiceReturnView.vue + /leo/voice/topup/return route                  | todo   | —     |
| P8-FE-009  | Add route /leo/voice/topup/return to router/index.ts                        | todo   | —     |
| P8-FE-010  | Create VoiceCallLogView.vue (list of calls with status, duration, outcome)  | todo   | —     |

### DevOps

| ID         | Task                                                                         | Status | Owner |
|------------|------------------------------------------------------------------------------|--------|-------|
| P8-DO-001  | Add TWILIO_VOICE_* env vars to .env.example + config/services.php           | todo   | —     |
| P8-DO-002  | Add GET+POST /webhooks/leo/voice/* routes to routes/api.php                 | todo   | —     |

---

## Sprint 12 — Voice Credit Billing & Dashboard (Weeks 21–22)

### Backend

| ID         | Task                                                                         | Status | Owner |
|------------|------------------------------------------------------------------------------|--------|-------|
| P8-BE-020  | Update StripeWebhookController for voice credit checkout.session.completed   | todo   | —     |
| P8-BE-021  | Create RenewVoiceCreditJob (Stripe Invoice Item + topUp + mail)             | todo   | —     |
| P8-BE-022  | Create leo:renew-voice-credits Artisan command (monthly scheduler)           | todo   | —     |
| P8-BE-023  | Create VoiceCreditRenewedMail Mailable                                       | todo   | —     |
| P8-BE-024  | Update LeoChannelController::store() — 422 when Voice + cap = 0             | todo   | —     |
| P8-BE-025  | Create VoiceCallStatsResource (calls placed, answered, confirmed, cancelled) | todo   | —     |
| P8-BE-026  | Add stats endpoint to VoiceCreditController                                  | todo   | —     |

### Frontend

| ID         | Task                                                                         | Status | Owner |
|------------|------------------------------------------------------------------------------|--------|-------|
| P8-FE-020  | Create VoiceStatsCard.vue (calls placed, answered rate, confirmation rate)  | todo   | —     |
| P8-FE-021  | Update LandingView.vue Pricing section (Voice prepaid mention)              | todo   | —     |
| P8-FE-022  | Add Voice call log link to reservation detail panel                         | todo   | —     |

### DevOps

| ID         | Task                                                                         | Status | Owner |
|------------|------------------------------------------------------------------------------|--------|-------|
| P8-DO-020  | Update GitHub Actions CI with fake TWILIO_VOICE_* env vars                  | todo   | —     |
| P8-DO-021  | Document checkout.session.completed voice credit event in Stripe webhook    | todo   | —     |
| P8-DO-022  | Document Twilio Voice number setup + TwiML app config in Audit Notes        | todo   | —     |

---

## Audit Notes

| Date       | Note                                                                              |
|------------|-----------------------------------------------------------------------------------|
| 2026-03-14 | Initial generation — Phase 8 Voice Calls spec based on brainstorm 2026-03-14     |
| 2026-03-14 | Key decisions: French TwiML with Amazon Polly Léa voice; prepaid credit wallet mirrors WhatsApp pattern; DTMF gather 1=confirm 2=cancel; retry on no-answer (max 2 retries) |
