# Phase 2 — Task Tracking

> **Status**: Not started
> **Spec**: [docs/phases/phase2.md](../phases/phase2.md)
> **Last audit**: 2026-03-12

---

## Sprint 2 — Smart Reminders & Scoring (Week 3)

### Backend

| ID         | Task                                                                       | Status | Owner |
|------------|----------------------------------------------------------------------------|--------|-------|
| P2-BE-001  | Create ReliabilityScoreService (recalculate, getTierForScore)              | todo   | —     |
| P2-BE-002  | Implement RecalculateReliabilityScore job (replace Phase 1 stub)           | todo   | —     |
| P2-BE-003  | Create ReservationObserver (score dispatch + counter increments)           | todo   | —     |
| P2-BE-004  | Create ProcessScheduledReminders command (2h + 30min windows, lockForUpdate) | todo | —     |
| P2-BE-005  | Create AutoCancelExpiredReservations command (expired tokens + post-30min) | todo   | —     |
| P2-BE-006  | Configure Kernel.php scheduler (everyMinute, withoutOverlapping)           | todo   | —     |
| P2-BE-007  | Create SendReminderSms job (2h/30min bodies, flag updates, opted-out check) | todo  | —     |
| P2-BE-008  | Implement TwilioWebhookController (HMAC, status update, STOP opt-out)      | todo   | —     |
| P2-BE-009  | Add opted_out guard to SendVerificationSms and SendReminderSms             | todo   | —     |
| P2-BE-010  | Fix ReservationController::store to generate token for phone_verified flow | todo   | —     |
| P2-BE-011  | Update ReservationController responses with score_tier + opted_out         | todo   | —     |
| P2-BE-012  | Add PATCH /reservations/{id}/status endpoint                               | todo   | —     |
| P2-BE-013  | Create UpdateReservationStatusRequest form request                         | todo   | —     |

### Frontend

| ID         | Task                                                                       | Status | Owner |
|------------|----------------------------------------------------------------------------|--------|-------|
| P2-FE-001  | Add updateStatus action to useReservations composable                      | todo   | —     |
| P2-FE-002  | Create ReservationRow.vue (actions, undo window, emits updated)            | todo   | —     |
| P2-FE-003  | Create StatusBadge.vue (7 statuses, French labels, colors, aria)           | todo   | —     |
| P2-FE-004  | Update ReservationForm.vue to emit reservation data on success             | todo   | —     |
| P2-FE-005  | Create usePolling composable (start/stop, onMounted/onUnmounted)           | todo   | —     |
| P2-FE-006  | Create ReservationList.vue (sorted list, skeleton, empty state, polling)   | todo   | —     |

---

## Audit Notes

| Date       | Note               |
|------------|--------------------|
| 2026-03-12 | Initial generation |
| 2026-03-12 | Added "Design System — Références Obligatoires" section (colors.md, polices.md, 3 logos SVG, 3 templates HTML) + règles spécifiques badges statuts et fiabilité |
