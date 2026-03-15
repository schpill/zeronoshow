# APEX Context — Phase 11: In-App Help & Onboarding

**Task ID**: 11-in-app-help-onboarding
**Timestamp**: 2026-03-15T12:00:00+01:00
**Task**: Phase 11 — In-App Help & Onboarding (complete)
**Flags**: -a -x -s -t -b -pr -ci
**Branch**: apex/11-in-app-help-onboarding

## Acceptance Criteria

### Sprint 17 — Backend
- [ ] Migration `onboarding_completed_at` on `businesses` table
- [ ] `BusinessController::completeOnboarding()` — PATCH /api/v1/business/onboarding-complete (idempotent)
- [ ] `BusinessResource` includes `onboarding_completed_at`
- [ ] Auth payload includes `onboarding_completed_at`
- [ ] Pest tests pass

### Sprint 17 — Frontend
- [ ] `HelpTooltip.vue` — accessible, positioned tooltip component
- [ ] `OnboardingTour.vue` — 6-step overlay tour component
- [ ] `useOnboardingTour.ts` composable with steps + API call
- [ ] Tour integrated in AppLayout.vue
- [ ] `EmptyState.vue` — reusable empty state component
- [ ] Empty states on Dashboard, Waitlist, Customers, Reputation
- [ ] HelpTooltip on Dashboard (score, confirmation rate, create button)
- [ ] HelpTooltip on ReservationDetailPage (score badge, SMS statuses)
- [ ] HelpTooltip on LeoView (credit balance, Telegram connect)
- [ ] Vitest tests pass

### Sprint 18 — Help Center
- [ ] `/help` public route with child routes ✅ (done)
- [ ] `HelpLayout.vue` ✅ (done)
- [ ] `HelpIndexView.vue` with search ✅ (done)
- [ ] 8 help module pages (4 remaining: widget, waitlist, customers, reputation, leo)
- [ ] `MermaidDiagram.vue` ✅ (done)
- [ ] 3 `.mmd` diagram files
- [ ] `capture-screenshots.ts` Playwright script
- [ ] `docs-screenshots` Makefile target
- [ ] `mermaid` in package.json
- [ ] Vitest tests pass (3 remaining)

## Already Completed (from prior session)
- NavBar updated with "Aide" link
- ReliabilityBadge has `reliability-badge` class
- ReservationForm has `id="new-reservation-btn"`
- Dashboard has `id="dashboard-stats"` on StatsBar
- ReservationDetailPage has `id="sms-logs-section"`
- LeoView has `id="leo-connect-btn"` on create button
- Router has `/help` and `/help/:module` routes
- HelpIndexView, HelpModuleRouter, HelpReservationsView, HelpSmsView created
- MermaidDiagram.vue created
