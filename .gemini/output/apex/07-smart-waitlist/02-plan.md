# Phase 7 — Implementation Plan

## Overview
Implement the "Smart Waitlist" feature to automatically fill slots on reservation cancellation. This includes manual/public registration, automated notifications, and a cascading confirmation mechanism.

## Files to Create/Modify

### Sprint 9 — Core Backend & Dashboard Basics

| Path | Action | Description |
|------|--------|-------------|
| `backend/database/migrations/2026_03_30_000001_create_waitlist_entries_table.php` | Create | Migration for `waitlist_entries` |
| `backend/database/migrations/2026_03_30_000002_add_waitlist_config_to_businesses_table.php` | Create | Add waitlist config to businesses |
| `backend/app/Enums/WaitlistStatusEnum.php` | Create | Enum for waitlist status |
| `backend/app/Models/WaitlistEntry.php` | Create | Model for waitlist entries |
| `backend/app/Policies/WaitlistPolicy.php` | Create | Policy for waitlist access |
| `backend/app/Http/Requests/StoreWaitlistEntryRequest.php` | Create | Request for storing waitlist entries |
| `backend/app/Http/Resources/WaitlistEntryResource.php` | Create | Resource for waitlist entries |
| `backend/app/Http/Controllers/Api/WaitlistController.php` | Create | CRUD controller for waitlist |
| `backend/app/Services/WaitlistService.php` | Create | Service for waitlist business logic |
| `backend/app/Jobs/NotifyWaitlistJob.php` | Create | Job to notify the next waitlist entry |
| `backend/app/Jobs/ExpireWaitlistNotificationsJob.php` | Create | Job to expire notifications and cascade |
| `backend/app/Http/Controllers/Public/WaitlistConfirmController.php` | Create | Controller for confirmation/decline |
| `backend/app/Observers/ReservationObserver.php` | Modify | Trigger waitlist notification on cancellation |
| `backend/routes/api.php` | Modify | Register waitlist API routes |
| `backend/routes/web.php` | Modify | Register public waitlist routes |

### Sprint 9 — Frontend Basics

| Path | Action | Description |
|------|--------|-------------|
| `frontend/src/api/waitlist.ts` | Create | API client for waitlist |
| `frontend/src/composables/useWaitlist.ts` | Create | Composable for waitlist management |
| `frontend/src/views/WaitlistView.vue` | Create | Authenticated waitlist management page |
| `frontend/src/components/waitlist/WaitlistEntryRow.vue` | Create | Table row for waitlist entry |
| `frontend/src/components/waitlist/WaitlistStatusBadge.vue` | Create | Badge for waitlist status |
| `frontend/src/components/waitlist/AddWaitlistEntryModal.vue` | Create | Modal for manual registration |
| `frontend/src/router/index.ts` | Modify | Register waitlist routes |

### Sprint 10 — Public Registration & Polish

| Path | Action | Description |
|------|--------|-------------|
| `backend/app/Http/Controllers/Public/PublicWaitlistController.php` | Create | Controller for public registration |
| `backend/app/Http/Requests/PublicStoreWaitlistRequest.php` | Create | Request for public waitlist registration |
| `backend/app/Services/WaitlistPublicLinkService.php` | Create | Service for public link management |
| `backend/app/Http/Controllers/Api/WaitlistSettingsController.php` | Create | Controller for waitlist settings |
| `backend/app/Http/Requests/WaitlistSettingsRequest.php` | Create | Request for waitlist settings |
| `backend/app/Leo/Tools/GetPendingReservationsTool.php` | Modify | Include waitlist count in Léo tool |
| `frontend/src/views/public/PublicWaitlistView.vue` | Create | Public self-registration page |
| `frontend/src/components/waitlist/WaitlistSettingsCard.vue` | Create | Card for waitlist settings management |

## Implementation Order

1.  **Backend Sprint 9 Core**: Migrations, Enum, Model, Policy, Resources.
2.  **Backend Sprint 9 Logic**: `WaitlistService`, `NotifyWaitlistJob`, `ExpireWaitlistNotificationsJob`.
3.  **Backend Sprint 9 API**: `WaitlistController`, `WaitlistConfirmController`, Routes.
4.  **Backend Sprint 9 Observer**: Update `ReservationObserver`.
5.  **Frontend Sprint 9 Core**: API client, Composable.
6.  **Frontend Sprint 9 UI**: Components, Views, Router.
7.  **Sprint 10 Backend**: Public registration, settings, Léo tool update.
8.  **Sprint 10 Frontend**: Public registration view, settings card.
9.  **Validation & Testing**: Run all tests (TDD).

## Acceptance Criteria

- [ ] A business owner can manually add a client to the waitlist from the dashboard.
- [ ] A client can self-register via a public link.
- [ ] Reservation cancellation triggers the waitlist notification process.
- [ ] Clients receive an SMS with a confirmation link.
- [ ] Confirming creates a reservation; declining notifies the next entry.
- [ ] Expired notifications automatically trigger the next entry notification.
- [ ] All tests pass with ≥ 80% coverage.
- [ ] Pint, PHPStan, ESLint, Prettier pass.
