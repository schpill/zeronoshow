# Phase 2 Smart Reminders And Reliability Scoring Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Deliver the full Phase 2 scope described in `docs/phases/phase2.md` and `docs/dev/phase2.md`, including reminder scheduling, reliability scoring, Twilio webhook handling, reservation status updates, frontend list/actions, tests, and CI-ready integration.

**Architecture:** The backend remains the source of truth for reservation lifecycle, customer score computation, reminder dispatch, and Twilio delivery state. Phase 2 extends the existing Laravel API with new customer fields, observer-driven score recalculation, scheduler commands, reminder jobs, and a webhook. The frontend consumes those APIs through the current Vue SPA, adds polling plus actionable reservation rows, and renders score/status badges using the mandated design tokens.

**Tech Stack:** Laravel 12, PHP 8.3, Sanctum, queues/jobs, Twilio SDK, Vue 3, TypeScript, Vitest, Tailwind.

---

### Task 1: Lock The Data Model

**Files:**
- Create: `backend/database/migrations/2026_03_19_000001_add_opted_out_to_customers.php`
- Create: `backend/database/migrations/2026_03_19_000002_add_score_tier_to_customers.php`
- Modify: `backend/app/Models/Customer.php`
- Modify: `backend/database/factories/CustomerFactory.php`
- Test: `backend/tests/Unit/Services/ReliabilityScoreServiceTest.php`

**Steps:**
1. Write the failing unit tests for score calculation and persistence.
2. Run the targeted backend test to verify it fails for missing service/fields.
3. Add migrations and model/factory updates with `opted_out`, `opted_out_at`, and `score_tier`.
4. Re-run the targeted test and keep it red until the service exists.

### Task 2: Implement Reliability Score Recalculation

**Files:**
- Create: `backend/app/Services/ReliabilityScoreService.php`
- Modify: `backend/app/Jobs/RecalculateReliabilityScore.php`
- Test: `backend/tests/Unit/Services/ReliabilityScoreServiceTest.php`
- Test: `backend/tests/Unit/Jobs/RecalculateReliabilityScoreTest.php`

**Steps:**
1. Write failing tests for `ReliabilityScoreService` and `RecalculateReliabilityScore`.
2. Run targeted tests and confirm the expected failures.
3. Implement minimal service logic and job behavior, including retries/backoff and cache invalidation.
4. Re-run the targeted tests until green.

### Task 3: Wire Reservation Lifecycle Updates

**Files:**
- Create: `backend/app/Observers/ReservationObserver.php`
- Modify: `backend/app/Providers/AppServiceProvider.php`
- Modify: `backend/app/Models/Reservation.php`
- Test: `backend/tests/Unit/Observers/ReservationObserverTest.php`
- Test: `backend/tests/Feature/Reservation/UpdateStatusTest.php`

**Steps:**
1. Write failing tests for observer dispatch/counters and reservation status update endpoint behavior.
2. Run the targeted tests and verify red.
3. Implement observer registration, terminal-status detection, counters, and status timestamping.
4. Re-run targeted tests until green.

### Task 4: Deliver Reminder And Auto-Cancel Automation

**Files:**
- Create: `backend/app/Console/Commands/ProcessScheduledReminders.php`
- Create: `backend/app/Console/Commands/AutoCancelExpiredReservations.php`
- Create: `backend/app/Jobs/SendReminderSms.php`
- Modify: `backend/routes/console.php`
- Test: `backend/tests/Feature/Commands/ProcessScheduledRemindersTest.php`
- Test: `backend/tests/Feature/Commands/AutoCancelExpiredTest.php`
- Test: `backend/tests/Unit/Jobs/SendReminderSmsTest.php`

**Steps:**
1. Write failing tests for reminder dispatch windows, auto-cancel rules, and reminder SMS bodies/guards.
2. Run the targeted tests to confirm failure.
3. Implement commands and reminder job with the minimum logic needed.
4. Re-run targeted tests until green.

### Task 5: Complete Twilio And Reservation APIs

**Files:**
- Create: `backend/app/Http/Requests/UpdateReservationStatusRequest.php`
- Modify: `backend/app/Http/Controllers/Api/ReservationController.php`
- Modify: `backend/app/Http/Controllers/Webhook/TwilioWebhookController.php`
- Modify: `backend/app/Http/Resources/ReservationResource.php`
- Modify: `backend/app/Jobs/SendVerificationSms.php`
- Modify: `backend/routes/api.php`
- Test: `backend/tests/Feature/Webhook/TwilioWebhookTest.php`
- Test: `backend/tests/Feature/Reservation/StoreReservationTest.php`
- Test: `backend/tests/Feature/Reservation/UpdateStatusTest.php`

**Steps:**
1. Write failing tests for webhook HMAC, STOP handling, store response fields, and update-status endpoint.
2. Run the targeted tests to verify they fail for the correct reasons.
3. Implement request validation, controller updates, webhook processing, and SMS opted-out guard.
4. Re-run the targeted tests until green.

### Task 6: Add Frontend Reservation Actions And Polling

**Files:**
- Create: `frontend/src/components/StatusBadge.vue`
- Create: `frontend/src/components/ReservationRow.vue`
- Create: `frontend/src/components/ReservationList.vue`
- Create: `frontend/src/composables/usePolling.ts`
- Modify: `frontend/src/composables/useReservations.ts`
- Modify: `frontend/src/components/ReservationForm.vue`
- Modify: `frontend/src/components/ReliabilityBadge.vue`
- Modify: `frontend/src/pages/Dashboard.vue`
- Modify: `frontend/src/types/reservations.ts`
- Test: `frontend/src/composables/__tests__/usePolling.spec.ts`
- Test: `frontend/src/components/__tests__/StatusBadge.spec.ts`
- Test: `frontend/src/components/__tests__/ReservationRow.spec.ts`
- Test: `frontend/src/components/__tests__/ReservationList.spec.ts`
- Test: `frontend/src/components/__tests__/ReservationForm.spec.ts`
- Test: `frontend/src/composables/__tests__/useReservations.spec.ts`

**Steps:**
1. Write failing frontend tests for polling, status badges, reservation row actions, list behavior, and updated composable/form behavior.
2. Run targeted Vitest files and confirm red.
3. Implement minimal Vue/TypeScript changes respecting `docs/graphics/colors.md` and `docs/graphics/polices.md`.
4. Re-run targeted frontend tests until green.

### Task 7: Validate The Whole Phase

**Files:**
- Modify: `docs/dev/phase2.md`

**Steps:**
1. Run backend targeted suites, then the full backend suite.
2. Run frontend targeted suites, then build/type-check/tests.
3. Update task tracking in `docs/dev/phase2.md`.
4. Review the Phase 2 checklist against the implementation.

### Task 8: Finish Integration

**Files:**
- Modify: git history only

**Steps:**
1. Run final verification commands.
2. Perform adversarial review and fix issues found.
3. Commit coherent changes.
4. Push branch.
5. Create PR.
6. Watch CI and iterate until checks pass or a hard blocker is reached.
