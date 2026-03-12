# Phase 3 — Task Tracking

> **Status**: Not started
> **Spec**: [docs/phases/phase3.md](../phases/phase3.md)
> **Last audit**: 2026-03-12

---

## Sprint 3 — Dashboard & Billing (Week 4)

### Backend

| ID         | Task                                                                         | Status | Owner |
|------------|------------------------------------------------------------------------------|--------|-------|
| P3-BE-001  | Install and configure Laravel Horizon                                         | todo   | —     |
| P3-BE-002  | Install email library (Resend or SMTP), configure config/mail.php            | todo   | —     |
| P3-BE-003  | Create TrialExpiryWarning Mailable (Blade view, subject, CTA)                | todo   | —     |
| P3-BE-004  | Create SendTrialExpiryEmails command (48h window, hourly schedule)           | todo   | —     |
| P3-BE-005  | Create StripeService (createCheckoutSession, createInvoiceItem)              | todo   | —     |
| P3-BE-006  | Create SubscriptionController (checkout, show endpoints)                     | todo   | —     |
| P3-BE-007  | Create StripeWebhookController (signature validation, event handling)        | todo   | —     |
| P3-BE-008  | Create SyncMonthlySmsCost command (1st of month, idempotent Invoice Items)   | todo   | —     |
| P3-BE-009  | Implement DashboardController::index (stats, cost, Redis cache 30s)          | todo   | —     |
| P3-BE-010  | Update RequireActiveSubscription middleware (full logic, allow GET)           | todo   | —     |
| P3-BE-011  | Create trial-expiry Blade email template (HTML + text)                       | todo   | —     |

### Frontend

| ID         | Task                                                                         | Status | Owner |
|------------|------------------------------------------------------------------------------|--------|-------|
| P3-FE-001  | Replace stub Dashboard.vue with full implementation (date nav, toggle, form) | todo   | —     |
| P3-FE-002  | Create StatsBar.vue (4 stat cards, responsive, role=status)                  | todo   | —     |
| P3-FE-003  | Create DateNavigator.vue (prev/next/today, keyboard accessible)              | todo   | —     |
| P3-FE-004  | Create SubscriptionPage.vue (status, CTA, success/cancel handling)           | todo   | —     |
| P3-FE-005  | Create TrialBanner.vue (days remaining, red variant, dismissable)            | todo   | —     |
| P3-FE-006  | Create ReservationDetailPage.vue (full details, SMS logs, actions)           | todo   | —     |
| P3-FE-007  | Create SmsLogTable.vue (type/status/cost/dates, empty state)                 | todo   | —     |
| P3-FE-008  | Update NavBar.vue with subscription link + status indicator                  | todo   | —     |
| P3-FE-009  | Create useSubscription composable (status, daysUntilTrialEnd)                | todo   | —     |
| P3-FE-010  | Add weekly view to Dashboard.vue (7-day grouping, no-show rate)              | todo   | —     |
| P3-FE-011  | Implement dark mode: DarkModeToggle.vue, anti-flash script, dark: variants on all components | todo | — |

---

## Audit Notes

| Date       | Note               |
|------------|--------------------|
| 2026-03-12 | Initial generation |
| 2026-03-12 | Added P3-FE-011 (dark mode toggle + dark: variants); updated FE test/exit criteria counts |
| 2026-03-12 | Added "Design System — Références Obligatoires" section avec règles dark mode, TrialBanner, SubscriptionPage, logo NavBar |
