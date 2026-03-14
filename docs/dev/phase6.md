# Phase 6 — Task Tracking

> **Status**: Implemented
> **Spec**: [docs/phases/phase6.md](../phases/phase6.md)
> **Last audit**: 2026-03-14

---

## Sprint 7 — WhatsApp Core Backend (Weeks 11–12)

### Backend

| ID         | Task                                                                         | Status | Owner |
|------------|------------------------------------------------------------------------------|--------|-------|
| P6-BE-001  | Create WhatsAppChannel implementing LeoChannelInterface (3 methods)          | done   | Codex |
| P6-BE-002  | Migration: add_whatsapp_credits_to_businesses_table                          | done   | Codex |
| P6-BE-003  | Migration: create_whatsapp_conversation_windows_table                        | done   | Codex |
| P6-BE-004  | Create WhatsAppConversationWindow model (HasUuids, scopes active/forContact) | done   | Codex |
| P6-BE-005  | Create LeoWhatsAppConversationTracker (hasActiveWindow, openWindow, purge)   | done   | Codex |
| P6-BE-006  | Create LeoWhatsAppCreditService (deduct, topUp, suspend, hasSufficient)      | done   | Codex |
| P6-BE-007  | Add WhatsApp cost config to config/leo.php (service/utility/threshold cents) | done   | Codex |
| P6-BE-008  | Register WhatsAppChannel in AppServiceProvider + config/services.php         | done   | Codex |
| P6-BE-009  | Add whatsapp() method to LeoWebhookController (GET verify + POST inbound)   | done   | Codex |
| P6-BE-010  | Create LeoWhatsAppCreditExhaustedEvent + LeoWhatsAppLowBalanceEvent          | done   | Codex |
| P6-BE-011  | Create SendCreditExhaustedNotification + SendLowBalanceNotification listeners| done   | Codex |
| P6-BE-012  | Create WhatsAppCreditExhaustedMail + WhatsAppLowBalanceMail Mailables        | done   | Codex |
| P6-BE-013  | Create PurgeWhatsAppConversationWindows Artisan command (daily scheduler)    | done   | Codex |

### Frontend

| ID         | Task                                                                         | Status | Owner |
|------------|------------------------------------------------------------------------------|--------|-------|
| P6-FE-001  | Enable WhatsApp in CreateLeoChannelForm.vue + inline budget setup section    | done   | Codex |
| P6-FE-002  | Create src/api/whatsappCredits.ts (typed client + WhatsAppCreditStatus type) | done   | Codex |
| P6-FE-003  | Create src/composables/useWhatsAppCredits.ts (fetch, topUp, setCap, computed)| done   | Codex |
| P6-FE-004  | Create WhatsAppCreditCard.vue (balance bar, low-balance warning, top-up CTA) | done   | Codex |
| P6-FE-005  | Create WhatsAppTopUpModal.vue (preset buttons + custom input + Stripe CTA)   | done   | Codex |
| P6-FE-006  | Update LeoView.vue — mount WhatsAppCreditCard for WhatsApp channel type      | done   | Codex |

### DevOps

| ID         | Task                                                                         | Status | Owner |
|------------|------------------------------------------------------------------------------|--------|-------|
| P6-DO-001  | Add GET+POST /webhooks/leo/whatsapp routes to routes/api.php                 | done   | Codex |
| P6-DO-002  | Add WHATSAPP_* env vars to .env.example + config/services.php                | done   | Codex |

---

## Sprint 8 — Credit Billing, Dashboard & Polish (Weeks 13–14)

### Backend

| ID         | Task                                                                         | Status | Owner |
|------------|------------------------------------------------------------------------------|--------|-------|
| P6-BE-020  | Create LeoWhatsAppCreditController (status, topup, setCap)                   | done   | Codex |
| P6-BE-021  | Create TopUpWhatsAppRequest (amount_cents 100–10000, French errors)          | done   | Codex |
| P6-BE-022  | Create SetWhatsAppCapRequest (monthly_cap_cents, auto_renew)                 | done   | Codex |
| P6-BE-023  | Create LeoWhatsAppCreditResource (balance_euros, cap_euros, low_balance)     | done   | Codex |
| P6-BE-024  | Update StripeWebhookController for checkout.session.completed (WA credit)    | done   | Codex |
| P6-BE-025  | Create RenewWhatsAppCreditJob (Stripe Invoice Item + topUp + mail)           | done   | Codex |
| P6-BE-026  | Create leo:renew-whatsapp-credits Artisan command (monthly scheduler)        | done   | Codex |
| P6-BE-027  | Create WhatsAppCreditRenewedMail Mailable                                    | done   | Codex |
| P6-BE-028  | Create leo:setup-whatsapp-webhook Artisan command (Meta webhook registration)| done   | Codex |
| P6-BE-029  | Update LeoChannelController::store() — 422 when WhatsApp + cap = 0           | done   | Codex |

### Frontend

| ID         | Task                                                                         | Status | Owner |
|------------|------------------------------------------------------------------------------|--------|-------|
| P6-FE-020  | Create WhatsAppReturnView.vue + /leo/whatsapp/topup/return route             | done   | Codex |
| P6-FE-021  | Add route /leo/whatsapp/topup/return to router/index.ts                      | done   | Codex |
| P6-FE-022  | Update LandingView.vue Pricing section (WhatsApp prepaid mention)            | done   | Codex |
| P6-FE-023  | Create WhatsAppCapEditForm.vue (inline budget + auto-renew edit)             | done   | Codex |

### DevOps

| ID         | Task                                                                         | Status | Owner |
|------------|------------------------------------------------------------------------------|--------|-------|
| P6-DO-020  | Update GitHub Actions CI with fake WHATSAPP_* env vars                       | done   | Codex |
| P6-DO-021  | Document adding checkout.session.completed to Stripe webhook events          | done   | Codex |
| P6-DO-022  | Document Meta WhatsApp Business account setup steps in Audit Notes           | done   | Codex |

---

## Audit Notes

| Date       | Note                                                                                                                             |
|------------|----------------------------------------------------------------------------------------------------------------------------------|
| 2026-03-14 | Initial generation — Phase 6 WhatsApp channel + prepaid credit wallet spec based on conversation 2026-03-14                     |
| 2026-03-14 | Key decisions: Telegram stays free (included in 9€ Léo add-on); WhatsApp = prepaid credits (like OpenRouter model); credits roll over indefinitely; monthly auto-renewal via Stripe Invoice Item (not a new Subscription); hard cap = current balance (not a configurable limit); channel suspended at balance = 0; single Meta phone number for all businesses (MVP); conversation windows tracked at application level to avoid double-charging |
| 2026-03-14 | Implémentation livrée: WhatsAppChannel, credit wallet (deduct/topUp/suspend), conversation windows 24h, Stripe top-up Checkout, renouvellement mensuel automatique, emails alerte solde faible/épuisé, commandes leo:renew-whatsapp-credits + leo:setup-whatsapp-webhook + whatsapp:purge-windows, frontend WhatsAppCreditCard + TopUpModal + CapEditForm + ReturnView |

### Meta WhatsApp Business Setup (run once at deploy)

```bash
# 1. Create Meta Business account at business.facebook.com
# 2. Add WhatsApp product to Meta App
# 3. Create System User (Admin) → generate permanent access token
# 4. Note Phone Number ID from WhatsApp → Getting Started

# 5. Register webhook
php artisan leo:setup-whatsapp-webhook

# 6. Verify webhook is active
curl -X GET "https://graph.facebook.com/v20.0/{PHONE_NUMBER_ID}" \
  -H "Authorization: Bearer {WHATSAPP_ACCESS_TOKEN}"
```

### Stripe Webhook Configuration (manual step)

Add `checkout.session.completed` to the allowed events on the existing ZeroNoShow Stripe webhook endpoint:

1. Stripe Dashboard → Developers → Webhooks → select endpoint
2. Click "Add events" → search `checkout.session.completed` → add
3. Note: existing `customer.subscription.*` events are unchanged
