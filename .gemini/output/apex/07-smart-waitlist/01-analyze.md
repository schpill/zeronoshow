# Phase 7 — Smart Waitlist Analysis

## Project Overview
The project is a reservation management system with a focus on reducing no-shows (Léo). It already has a working reservation system, a dashboard, and a WhatsApp integration (Phase 6).

## Existing Patterns

### Backend (Laravel)
- **Models**: Use `HasUuids` and standard Eloquent relations.
- **Migrations**: Standard Laravel migrations, following a specific naming and sequencing pattern.
- **Controllers**: API controllers return JSON, often using Laravel Resources.
- **Observers**: `ReservationObserver` handles side effects of reservation changes (status changes, bumping dashboard version, dispatching jobs).
- **Jobs**: Background jobs for sending notifications (SMS, WhatsApp) and recalculating scores.
- **Policies**: standard Laravel policies for authorization.

### Frontend (Vue 3 + Vite)
- **Router**: Uses `vue-router` with `requiresAuth` meta for protected routes.
- **Stores**: Uses Pinia (e.g., `useAuthStore`).
- **Composables**: For logic reuse (API calls, polling, toasts).
- **Components**: Atomic and functional components, often with corresponding tests.
- **API**: Centralized API client using `axios` with error handling.

## Phase 7 Requirements
- **Waitlist Management**: Manual and public registration.
- **Automated Slot Recovery**: Cancellation triggers notification to waitlist entries.
- **Time-limited Confirmation**: Clients have a window to confirm.
- **Cascade**: If no response, notify next in line.
- **Dashboard UI**: Manage waitlist, reorder, and manual notify.
- **Public UI**: Self-registration and confirmation/decline views.

## Dependencies to be added/used
- **Twilio**: For SMS (already in use).
- **WhatsApp**: For notifications (already in use).
- **Laravel Scheduler**: For expiring notifications.
