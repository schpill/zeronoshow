# Phase 11 — Task Tracking

> **Status**: Not started
> **Spec**: [docs/phases/phase11.md](../phases/phase11.md)
> **Last audit**: 2026-03-15

---

## Sprint 17 — Onboarding Tour, Tooltips & Empty States (Weeks 31–32)

### Backend

| ID         | Task                                                              | Status | Owner |
|------------|-------------------------------------------------------------------|--------|-------|
| P11-BE-001 | Migration: add `onboarding_completed_at` to `businesses`         | todo   | —     |
| P11-BE-002 | `PATCH /api/v1/business/onboarding-complete` endpoint            | todo   | —     |
| P11-BE-003 | Include `onboarding_completed_at` in auth/me response            | todo   | —     |

### Frontend

| ID         | Task                                                                           | Status | Owner |
|------------|--------------------------------------------------------------------------------|--------|-------|
| P11-FE-001 | Create `HelpTooltip.vue` component                                             | todo   | —     |
| P11-FE-002 | Create `OnboardingTour.vue` component (6-step overlay)                         | todo   | —     |
| P11-FE-003 | Define tour steps in `useOnboardingTour.ts` composable                         | todo   | —     |
| P11-FE-004 | Integrate `OnboardingTour` in `AppLayout.vue` with API call on complete/skip   | todo   | —     |
| P11-FE-005 | Create `EmptyState.vue` component                                              | todo   | —     |
| P11-FE-006 | Add `EmptyState` to `Dashboard.vue` (no reservations today)                   | todo   | —     |
| P11-FE-007 | Add `EmptyState` to `WaitlistView.vue`                                         | todo   | —     |
| P11-FE-008 | Add `EmptyState` to `CustomersView.vue`                                        | todo   | —     |
| P11-FE-009 | Add `EmptyState` to `ReputationView.vue`                                       | todo   | —     |
| P11-FE-010 | Add `HelpTooltip` to `Dashboard.vue` (score, confirmation rate, create btn)    | todo   | —     |
| P11-FE-011 | Add `HelpTooltip` to `ReservationDetailPage.vue` (score badge, SMS statuses)   | todo   | —     |
| P11-FE-012 | Add `HelpTooltip` to `LeoView.vue` (credit balance, Telegram connect)          | todo   | —     |

---

## Sprint 18 — Help Center, Screenshots & Flow Diagrams (Weeks 33–34)

### Frontend

| ID         | Task                                                                           | Status | Owner |
|------------|--------------------------------------------------------------------------------|--------|-------|
| P11-FE-020 | Add `/help` public route and `/help/:module?` child route to router           | todo   | —     |
| P11-FE-021 | Create `HelpLayout.vue` (sidebar + main content, responsive)                  | todo   | —     |
| P11-FE-022 | Create `HelpIndexView.vue` (landing page, client-side search, 8 module cards) | todo   | —     |
| P11-FE-023 | Create `HelpReservationsView.vue` with screenshot + lifecycle diagram          | todo   | —     |
| P11-FE-024 | Create `HelpSmsView.vue` with SMS pipeline diagram + screenshot                | todo   | —     |
| P11-FE-025 | Create `HelpScoringView.vue` with scoring diagram + tier examples              | todo   | —     |
| P11-FE-026 | Create `HelpWidgetView.vue` with screenshots + iframe embed example            | todo   | —     |
| P11-FE-027 | Create `HelpWaitlistView.vue`, `HelpCustomersView.vue`, `HelpReputationView.vue`, `HelpLeoView.vue` | todo | — |
| P11-FE-028 | Create `MermaidDiagram.vue` (dynamic import of `mermaid`, renders SVG)        | todo   | —     |
| P11-FE-029 | Create `.mmd` diagram files in `frontend/public/docs/diagrams/`               | todo   | —     |
| P11-FE-030 | Create `scripts/capture-screenshots.ts` Playwright screenshot script          | todo   | —     |
| P11-FE-031 | Add `docs-screenshots` target to `Makefile`                                   | todo   | —     |
| P11-FE-032 | Implement client-side search in `HelpIndexView.vue`                           | todo   | —     |

### DevOps

| ID         | Task                                                                       | Status | Owner |
|------------|----------------------------------------------------------------------------|--------|-------|
| P11-DO-040 | Add `mermaid` to `package.json` devDependencies, regenerate lockfile       | todo   | —     |
| P11-DO-041 | Document `make docs-screenshots` in Makefile comment                       | todo   | —     |

---

## Audit Notes

| Date       | Note                  |
|------------|-----------------------|
| 2026-03-15 | Initial generation    |
