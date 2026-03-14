# APEX Plan — 06-whatsapp-prepaid-credits

## Overview
Implement Phase 6 — WhatsApp channel with Meta Cloud API and a prepaid credit system including Stripe integration.

## Files to Create/Modify

### Backend
| Path | Action | Description |
|------|--------|-------------|
| `backend/app/Services/Leo/WhatsAppChannel.php` | Create | Implementation of LeoChannelInterface for Meta Cloud API. |
| `backend/database/migrations/2026_03_14_000001_add_whatsapp_credits_to_businesses_table.php` | Create | Add credit columns to businesses. |
| `backend/database/migrations/2026_03_14_000002_create_whatsapp_conversation_windows_table.php` | Create | Track 24h Meta conversation windows. |
| `backend/app/Models/WhatsAppConversationWindow.php` | Create | Model for windows. |
| `backend/app/Services/Leo/LeoWhatsAppConversationTracker.php` | Create | Service for window management. |
| `backend/app/Services/Leo/LeoWhatsAppCreditService.php` | Create | Service for credits, deduction, and suspension. |
| `backend/config/leo.php` | Modify | Add costs and Meta configuration. |
| `backend/app/Providers/AppServiceProvider.php` | Modify | Register WhatsAppChannel. |
| `backend/app/Http/Controllers/Webhook/LeoWebhookController.php` | Modify | Add whatsapp methods (GET/POST). |
| `backend/app/Events/LeoWhatsAppCreditExhaustedEvent.php` | Create | Exhaustion event. |
| `backend/app/Events/LeoWhatsAppLowBalanceEvent.php` | Create | Low balance event. |
| `backend/app/Listeners/SendCreditExhaustedNotification.php` | Create | Exhaustion listener. |
| `backend/app/Listeners/SendLowBalanceNotification.php` | Create | Low balance listener. |
| `backend/app/Mail/WhatsAppCreditExhaustedMail.php` | Create | Exhaustion mail. |
| `backend/app/Mail/WhatsAppLowBalanceMail.php` | Create | Low balance mail. |
| `backend/app/Console/Commands/PurgeWhatsAppConversationWindows.php` | Create | Daily cleanup. |
| `backend/app/Http/Controllers/Api/LeoWhatsAppCreditController.php` | Create | Credit management API. |
| `backend/app/Http/Requests/TopUpWhatsAppRequest.php` | Create | Top-up validation. |
| `backend/app/Http/Requests/SetWhatsAppCapRequest.php` | Create | Cap validation. |
| `backend/app/Http/Resources/LeoWhatsAppCreditResource.php` | Create | Resource for credits. |
| `backend/app/Http/Controllers/Webhook/StripeWebhookController.php` | Modify | Handle WA credit top-ups. |
| `backend/app/Jobs/RenewWhatsAppCreditJob.php` | Create | Monthly renewal job. |
| `backend/app/Console/Commands/RenewWhatsAppCredits.php` | Create | Renewal trigger. |
| `backend/app/Mail/WhatsAppCreditRenewedMail.php` | Create | Renewal confirmation mail. |
| `backend/app/Console/Commands/SetupWhatsAppWebhook.php` | Create | Meta webhook registration. |
| `backend/routes/api.php` | Modify | Routes for WA. |
| `backend/app/Http/Controllers/Api/LeoChannelController.php` | Modify | Store validation. |

### Frontend
| Path | Action | Description |
|------|--------|-------------|
| `frontend/src/api/whatsappCredits.ts` | Create | API client. |
| `frontend/src/composables/useWhatsAppCredits.ts` | Create | Credits composable. |
| `frontend/src/components/leo/WhatsAppCreditCard.vue` | Create | Credit card component. |
| `frontend/src/components/leo/WhatsAppTopUpModal.vue` | Create | Top-up modal. |
| `frontend/src/components/leo/WhatsAppCapEditForm.vue` | Create | Budget edit form. |
| `frontend/src/components/leo/CreateLeoChannelForm.vue` | Modify | Enable WA + budget setup. |
| `frontend/src/views/LeoView.vue` | Modify | Dashboard integration. |
| `frontend/src/views/WhatsAppReturnView.vue` | Create | Stripe return view. |
| `frontend/src/router/index.ts` | Modify | Route registration. |
| `frontend/src/views/LandingView.vue` | Modify | Pricing update. |

## Implementation Order
1. **Migrations & Models**
2. **Core Services & Configuration**
3. **Webhook & Channel Implementation**
4. **Billing & Stripe Integration**
5. **Frontend API & Composables**
6. **Frontend UI Components**

## Acceptance Criteria
- [ ] WhatsApp channel activation works with budget.
- [ ] Inbound messages processed (Gemini response).
- [ ] Credits deducted per conversation window.
- [ ] Auto-suspension at 0 balance works.
- [ ] Stripe top-up adds credits.
- [ ] Monthly renewal charges Stripe.
- [ ] Dashboard shows credit status correctly.
- [ ] All tests pass.
