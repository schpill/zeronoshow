# Phase 7 — Task Tracking

> **Status**: Not started
> **Spec**: [docs/phases/phase7.md](../phases/phase7.md)
> **Last audit**: 2026-03-14

---

## Sprint 9 — Waitlist Backend & Public Join (Weeks 15–16)

### Backend

| ID         | Task                                                                         | Status | Owner |
|------------|------------------------------------------------------------------------------|--------|-------|
| P7-BE-001  | Migration: create_waitlist_entries_table                                     | todo   | —     |
| P7-BE-002  | Migration: add_waitlist_config_to_businesses_table                           | todo   | —     |
| P7-BE-003  | Create WaitlistEntry model (HasUuids, scopes, relationships)                 | todo   | —     |
| P7-BE-004  | Create WaitlistService (notifyNext, confirmSlot, declineSlot, expire)        | todo   | —     |
| P7-BE-005  | Create NotifyWaitlistJob (dispatch from ReservationObserver on cancellation) | todo   | —     |
| P7-BE-006  | Create ExpireWaitlistNotificationsJob (everyMinute scheduler)                | todo   | —     |
| P7-BE-007  | Update ReservationObserver to trigger waitlist notification on cancellation  | todo   | —     |
| P7-BE-008  | Create WaitlistController (index, store, destroy, confirm, decline)          | todo   | —     |
| P7-BE-009  | Create StoreWaitlistEntryRequest (validation, French errors)                 | todo   | —     |
| P7-BE-010  | Create WaitlistEntryResource                                                 | todo   | —     |
| P7-BE-011  | Create PublicWaitlistController (join via public token, status check)        | todo   | —     |
| P7-BE-012  | Create WaitlistConfirmController (confirm/decline via notification token)    | todo   | —     |
| P7-BE-013  | Add waitlist routes (authenticated + public) to routes/api.php              | todo   | —     |
| P7-BE-014  | Create WaitlistNotifiedMail + WaitlistConfirmedMail Mailables               | todo   | —     |
| P7-BE-015  | Add public_token to businesses table (migration)                             | todo   | —     |
| P7-BE-016  | Create PurgeExpiredWaitlistEntries Artisan command (daily scheduler)         | todo   | —     |

### Frontend

| ID         | Task                                                                         | Status | Owner |
|------------|------------------------------------------------------------------------------|--------|-------|
| P7-FE-001  | Create src/api/waitlist.ts (typed client for waitlist endpoints)             | todo   | —     |
| P7-FE-002  | Create src/composables/useWaitlist.ts (fetch, add, remove, reactive state)  | todo   | —     |
| P7-FE-003  | Create WaitlistView.vue (main authenticated waitlist management page)        | todo   | —     |
| P7-FE-004  | Create WaitlistEntryRow.vue (entry card with status, actions)                | todo   | —     |
| P7-FE-005  | Create WaitlistStatusBadge.vue (pending/notified/confirmed/declined/expired) | todo   | —     |
| P7-FE-006  | Create AddWaitlistEntryModal.vue (manual add form)                           | todo   | —     |
| P7-FE-007  | Create PublicWaitlistView.vue (public self-registration page)                | todo   | —     |
| P7-FE-008  | Create WaitlistConfirmView.vue (guest confirms slot via notification link)   | todo   | —     |
| P7-FE-009  | Create WaitlistDeclineView.vue (guest declines slot)                         | todo   | —     |
| P7-FE-010  | Create WaitlistExpiredView.vue (token expired fallback page)                 | todo   | —     |
| P7-FE-011  | Add waitlist routes to router/index.ts (authenticated + public)             | todo   | —     |
| P7-FE-012  | Update sidebar navigation — add Waitlist link                               | todo   | —     |

---

## Sprint 10 — Waitlist Dashboard & Polish (Weeks 17–18)

### Backend

| ID         | Task                                                                         | Status | Owner |
|------------|------------------------------------------------------------------------------|--------|-------|
| P7-BE-020  | Create WaitlistStatsResource (queue depth, confirmation rate, avg wait time) | todo   | —     |
| P7-BE-021  | Add stats endpoint to WaitlistController                                     | todo   | —     |
| P7-BE-022  | Add waitlist_enabled toggle to business settings API                         | todo   | —     |
| P7-BE-023  | Update WaitlistConfirmController to check slot still available               | todo   | —     |
| P7-BE-024  | Create WaitlistConfigController (update notification_timeout_minutes, max)   | todo   | —     |

### Frontend

| ID         | Task                                                                         | Status | Owner |
|------------|------------------------------------------------------------------------------|--------|-------|
| P7-FE-020  | Create WaitlistStatsCard.vue (queue depth, confirmation rate, avg wait)      | todo   | —     |
| P7-FE-021  | Create WaitlistConfigPanel.vue (timeout, max entries, enable toggle)         | todo   | —     |
| P7-FE-022  | Create WaitlistShareCard.vue (public join link + copy button)                | todo   | —     |
| P7-FE-023  | Update LeoView.vue — add Waitlist section card                               | todo   | —     |
| P7-FE-024  | Add source badge to reservation list (waitlist origin)                       | todo   | —     |

### DevOps

| ID         | Task                                                                         | Status | Owner |
|------------|------------------------------------------------------------------------------|--------|-------|
| P7-DO-001  | Add WAITLIST_NOTIFICATION_TIMEOUT_MINUTES to .env.example                   | todo   | —     |
| P7-DO-002  | Update CI with fake waitlist env vars                                        | todo   | —     |

---

## Audit Notes

| Date       | Note                                                                         |
|------------|------------------------------------------------------------------------------|
| 2026-03-14 | Initial generation — Phase 7 Waitlist spec based on brainstorm 2026-03-14   |
