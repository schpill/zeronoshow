# Phase 11 — In-App Help & Onboarding

| Field            | Value                                                                 |
|------------------|-----------------------------------------------------------------------|
| **Phase**        | 11 of 12                                                              |
| **Name**         | In-App Help & Onboarding — Client Backoffice Documentation           |
| **Duration**     | Weeks 31–34 (4 weeks)                                                 |
| **Milestone**    | M11 — Client backoffice self-service ready                            |
| **PRD Sections** | §2 (Principles), §5 US-07 (onboarding time ≤ 5 min), §12 (UX Goals) |
| **Prerequisite** | Phase 10 fully completed and validated                                |
| **Status**       | Not started                                                           |

---

## Section 1 — Phase Objectives

| ID        | Objective                                                                                                           |
|-----------|---------------------------------------------------------------------------------------------------------------------|
| P11-OBJ-1 | New users complete a guided onboarding tour on first login that covers all core dashboard features                  |
| P11-OBJ-2 | Every major UI section has a contextual tooltip or help panel accessible without leaving the page                   |
| P11-OBJ-3 | A searchable help center (`/help`) documents all 8 client backoffice modules with screenshots and flow diagrams     |
| P11-OBJ-4 | A Playwright script auto-captures fresh screenshots for the help center on demand (`make docs-screenshots`)         |
| P11-OBJ-5 | Empty states on every data-less view guide users to the correct next action instead of showing a blank page         |
| P11-OBJ-6 | Onboarding progress is persisted per user — tour is never shown again after completion or explicit dismissal        |
| P11-OBJ-7 | Help center pages include Mermaid flow diagrams for complex processes (SMS pipeline, reservation lifecycle, scoring) |

---

## Section 2 — Entry Criteria

- Phase 10 (Booking Widget) merged and CI green on `main`
- All 8 dashboard modules stable: Dashboard, Reservations, Waitlist, Customers, Reputation, Léo, Voice, Subscription
- Vue Router nested routes structure in place (`AppLayout` as persistent shell — introduced Phase 10 refactor)
- Playwright installed in `frontend/` (`@playwright/test` in devDependencies)
- `businesses` table has `onboarding_completed_at TIMESTAMPTZ nullable` column (added this phase)
- Auth store (`useAuthStore`) exposes `user` object with `onboarding_completed_at` field

---

## Section 3 — Scope — Requirement Traceability

| Requirement Group                        | ID Range          | Status   | Notes                                                                   |
|------------------------------------------|-------------------|----------|-------------------------------------------------------------------------|
| Guided onboarding tour (first login)     | §5 US-07          | Included | 6-step tour covering dashboard, reservations, SMS logs, scoring, widget |
| Contextual tooltips per module           | §2 Principle 1    | Included | HelpTooltip component on every key metric and action button             |
| Help center page (`/help`)               | §12 UX Goals      | Included | 8 module pages, searchable, with screenshots + Mermaid diagrams         |
| Auto screenshot capture (Playwright)     | §12 UX Goals      | Included | `scripts/docs/capture-screenshots.ts` + `make docs-screenshots`        |
| Empty states on all data-less views      | §5 US-01, US-06   | Included | Dashboard, Reservations, Waitlist, Customers, Reputation                |
| Onboarding progress persistence          | §5 US-07          | Included | `onboarding_completed_at` on `businesses` table                         |
| Mermaid flow diagrams in help center     | §12 UX Goals      | Included | reservation_lifecycle.mmd, sms_pipeline.mmd, scoring.mmd                |
| Multi-language help content              | —                 | No       | French only (deferred)                                                  |
| Video walkthroughs                       | —                 | No       | Deferred post-MVP                                                       |
| In-app chat / live support               | —                 | No       | Out of scope                                                            |

---

## Section 4 — Detailed Sprint Breakdown

### 4.17 Sprint 17 — Onboarding Tour, Tooltips & Empty States (Weeks 31–32)

#### 4.17.1 Sprint Objectives

- `OnboardingTour.vue` renders a 6-step overlay tour on first login and never again after completion
- `HelpTooltip.vue` is used on every key metric and action button across the dashboard
- All 5 data-heavy views (Dashboard, Reservations, Waitlist, Customers, Reputation) have meaningful empty states
- `PATCH /api/v1/business/onboarding-complete` endpoint persists completion timestamp
- Onboarding state survives page refresh (loaded from `auth.user.onboarding_completed_at`)

#### 4.17.2 Database Migrations

| Migration name                              | Description                                                                                                                                                                    |
|---------------------------------------------|--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `add_onboarding_completed_at_to_businesses` | Add `onboarding_completed_at TIMESTAMPTZ nullable DEFAULT NULL` to `businesses`. Index: single-column btree on `onboarding_completed_at` (for operator reporting in Phase 12). |

#### 4.17.3 Back-end Tasks

| ID         | Task                                                                                                                                                                                                                        | PRD Ref        |
|------------|-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|----------------|
| P11-BE-001 | Add migration `add_onboarding_completed_at_to_businesses` — `onboarding_completed_at TIMESTAMPTZ nullable`. Add column to `Business` model `$fillable` and `$casts` (`datetime`). Add to `BusinessResource` response.       | §5 US-07       |
| P11-BE-002 | Add route `PATCH /api/v1/business/onboarding-complete` → `BusinessController::completeOnboarding(Request $r): JsonResponse`. Logic: if `auth()->user()->business->onboarding_completed_at` is null → set to `now()`, save, return updated `BusinessResource`. If already set → return 200 no-op (idempotent). | §5 US-07       |
| P11-BE-003 | Update `GET /api/v1/auth/me` (or `BusinessResource`) to include `onboarding_completed_at` field so the frontend can decide whether to show the tour on page load.                                                             | §5 US-07       |

#### 4.17.4 Back-end Tests (TDD)

| Test File                                                    | Test Cases                                                                                                                                              |
|--------------------------------------------------------------|---------------------------------------------------------------------------------------------------------------------------------------------------------|
| `tests/Feature/Business/OnboardingTest.php`                  | PATCH completes onboarding (sets timestamp), PATCH is idempotent if already completed, unauthenticated request → 401, `onboarding_completed_at` present in auth/me response, `BusinessResource` includes the field |

#### 4.17.5 Front-end Tasks

| ID         | Task                                                                                                                                                                                                                                                         | PRD Ref     |
|------------|--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|-------------|
| P11-FE-001 | Create `src/components/help/HelpTooltip.vue` — props: `content: string`, `position?: 'top'\|'bottom'\|'left'\|'right'` (default `'top'`), `icon?: boolean` (default `true`). Renders a `?` icon button; on click/hover shows a floating tooltip panel with `content`. Accessible: `role="tooltip"`, `aria-describedby`. Tailwind-styled (slate/emerald palette). | §2 P1 |
| P11-FE-002 | Create `src/components/help/OnboardingTour.vue` — 6-step tour overlay. Props: `modelValue: boolean` (v-model for visibility). Steps defined as `TourStep[]` (title, body, targetSelector, placement). Uses `getBoundingClientRect` to position spotlight overlay over target element. Emits `complete` and `skip`. | §5 US-07 |
| P11-FE-003 | Define tour steps array in `src/composables/useOnboardingTour.ts` — Step 1: "Votre tableau de bord" (targets `#dashboard-stats`), Step 2: "Créer une réservation" (targets `#new-reservation-btn`), Step 3: "Score de fiabilité" (targets `.reliability-badge`), Step 4: "Suivi SMS" (targets `#sms-logs-tab`), Step 5: "Widget de réservation" (targets `#widget-link`), Step 6: "Abonnement" (targets `#subscription-nav`). | §5 US-07 |
| P11-FE-004 | Integrate `OnboardingTour` in `src/layouts/AppLayout.vue` — show tour when `auth.user.onboarding_completed_at === null`. On `complete` or `skip`: call `PATCH /api/v1/business/onboarding-complete`, update auth store `user.onboarding_completed_at`. | §5 US-07 |
| P11-FE-005 | Create `src/components/help/EmptyState.vue` — props: `icon: string` (emoji or SVG name), `title: string`, `description: string`, `actionLabel?: string`, `actionTo?: string`. Renders centered illustration + text + optional CTA button (RouterLink). | §12 UX |
| P11-FE-006 | Add `EmptyState` to `src/pages/Dashboard.vue` — shown when `reservations.length === 0 && !loading`. Content: "Aucune réservation aujourd'hui", description: "Créez votre première réservation pour démarrer le suivi anti no-show.", action: "Créer une réservation". | §5 US-01 |
| P11-FE-007 | Add `EmptyState` to `src/views/WaitlistView.vue` — shown when waitlist entries are empty. Content: "Aucune liste d'attente active", action: "Créer une liste d'attente". | §5 US-01 |
| P11-FE-008 | Add `EmptyState` to `src/views/CustomersView.vue` — shown when customers table is empty. Content: "Aucun client encore", description: "Vos clients apparaîtront ici après leur première réservation.". No action CTA (data is auto-populated). | §9 CRM |
| P11-FE-009 | Add `EmptyState` to `src/views/ReputationView.vue` — shown when reviews list is empty. Content: "Aucun avis reçu", description: "Vos avis clients apparaîtront ici une fois la première demande envoyée.". | §9 Reputation |
| P11-FE-010 | Add `HelpTooltip` to `src/pages/Dashboard.vue` — on: "Score moyen" metric (explains cross-business scoring formula), "Taux de confirmation" metric (explains calculation), "Créer réservation" button (explains phone-verified checkbox). | §2 P1 |
| P11-FE-011 | Add `HelpTooltip` to `src/pages/ReservationDetailPage.vue` — on: reliability score badge (explains tier thresholds ≥90/70–89/<70), SMS log table header (explains queued/sent/delivered/failed statuses). | §2 P1 |
| P11-FE-012 | Add `HelpTooltip` to `src/views/LeoView.vue` — on: credit balance display (explains prepaid credit), Telegram bot connect button (explains how to link bot). | §5 Léo |

#### 4.17.6 Front-end Tests

| Test File                                                        | Test Cases                                                                                                                                                |
|------------------------------------------------------------------|-----------------------------------------------------------------------------------------------------------------------------------------------------------|
| `src/components/help/__tests__/HelpTooltip.test.ts`             | Renders `?` icon, tooltip hidden by default, shows content on click, keyboard accessible (Enter key shows tooltip), correct position class applied        |
| `src/components/help/__tests__/OnboardingTour.test.ts`          | Renders step 1 on mount, next step advances index, skip emits `skip` event and calls API, complete on last step emits `complete` and calls API, does not show when `modelValue=false` |
| `src/components/help/__tests__/EmptyState.test.ts`              | Renders icon + title + description, action CTA renders as RouterLink when `actionTo` provided, no CTA when `actionLabel` omitted                          |

#### 4.17.7 DevOps / Infrastructure Tasks

*(None — no new Docker services or CI changes.)*

#### 4.17.8 Deliverables Checklist

- [ ] `add_onboarding_completed_at_to_businesses` migration applied in local dev and test
- [ ] `PATCH /api/v1/business/onboarding-complete` returns 200 and is idempotent
- [ ] `OnboardingTour` shows on first login, never again after completion or skip
- [ ] Tour target elements have matching IDs/classes defined in respective page components
- [ ] `HelpTooltip` displayed on at least 8 key UI elements across the app
- [ ] Empty state shown on Dashboard, Reservations (filtered view with 0 results), Waitlist, Customers, Reputation
- [ ] All Sprint 17 Pest + Vitest tests passing
- [ ] CI pipeline green

---

### 4.18 Sprint 18 — Help Center, Screenshots & Flow Diagrams (Weeks 33–34)

#### 4.18.1 Sprint Objectives

- `/help` route renders a searchable help center with 8 module documentation pages
- Mermaid diagrams (`reservation_lifecycle.mmd`, `sms_pipeline.mmd`, `scoring.mmd`) render correctly in help pages
- `make docs-screenshots` runs a Playwright script that captures fresh UI screenshots into `frontend/public/docs/screenshots/`
- All help pages are accessible without authentication (public route)
- Help pages load in < 1 second (static content, no API calls)

#### 4.18.2 Database Migrations

*(No schema changes in Sprint 18.)*

#### 4.18.3 Back-end Tasks

*(No new backend endpoints — help center is entirely frontend-static.)*

#### 4.18.4 Front-end Tasks

| ID         | Task                                                                                                                                                                                                                             | PRD Ref     |
|------------|----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|-------------|
| P11-FE-020 | Add `/help` public route to `src/router/index.ts` (no `requiresAuth` meta). Add `/help/:module?` child route. Add `HelpLayout.vue` wrapper (sidebar nav + main content area). Exempt from `guestOnly` guard.                      | §12 UX      |
| P11-FE-021 | Create `src/layouts/HelpLayout.vue` — left sidebar listing 8 modules (RouterLinks to `/help/reservations`, `/help/sms`, `/help/scoring`, `/help/widget`, `/help/waitlist`, `/help/customers`, `/help/reputation`, `/help/leo`). Responsive: sidebar collapses to top nav on mobile. Back link to dashboard for authenticated users. | §12 UX |
| P11-FE-022 | Create `src/views/help/HelpIndexView.vue` — landing page at `/help`. Search input (client-side, filters module titles + content). Featured modules grid (8 cards with icon, title, short description). Link: "← Retour au tableau de bord" for authenticated users, "← Retour à l'accueil" for guests. | §12 UX |
| P11-FE-023 | Create `src/views/help/HelpReservationsView.vue` — documentation for the Reservations module. Sections: 1) Créer une réservation (with screenshot `reservations-create.png`), 2) Statuts de réservation (with `ReservationLifecycleDiagram`), 3) Marquer présent/absent, 4) Annulation automatique. | §7 Feature 1 |
| P11-FE-024 | Create `src/views/help/HelpSmsView.vue` — SMS pipeline documentation. Sections: 1) Types de SMS (with `SmsPipelineDiagram`), 2) Coût par SMS, 3) Webhook de statut de livraison. Screenshots: `sms-logs.png`. | §12 |
| P11-FE-025 | Create `src/views/help/HelpScoringView.vue` — reliability score documentation. Sections: 1) Calcul du score (with `ScoringDiagram`), 2) Les 3 niveaux (Fiable/Moyen/À risque with color-coded examples), 3) Impact sur les rappels. Screenshots: `reliability-badge.png`. | §7 Feature 2 |
| P11-FE-026 | Create `src/views/help/HelpWidgetView.vue` — booking widget documentation. Sections: 1) Obtenir le lien widget, 2) Intégration iframe (code example), 3) Personnalisation, 4) Page de succès. Screenshots: `widget-page.png`, `widget-embed-code.png`. | §8 |
| P11-FE-027 | Create `src/views/help/HelpWaitlistView.vue`, `HelpCustomersView.vue`, `HelpReputationView.vue`, `HelpLeoView.vue` — documentation pages for remaining modules. Each has 3–5 sections with screenshots and descriptive text. | §7, §9      |
| P11-FE-028 | Create `src/components/help/MermaidDiagram.vue` — dynamic component that renders a Mermaid `.mmd` string. Uses `mermaid` npm package (already available if installed; add to `package.json` if missing). Props: `definition: string`. Initializes `mermaid.render()` on mount. Outputs SVG inline. | §12 UX      |
| P11-FE-029 | Create Mermaid diagram definitions in `frontend/public/docs/diagrams/`: `reservation_lifecycle.mmd` (stateDiagram-v2 showing all reservation states and transitions), `sms_pipeline.mmd` (flowchart showing verification → reminder → auto-cancel path), `scoring.mmd` (flowchart of score tiers → reminder rules). Import and pass to `MermaidDiagram.vue` in relevant help views. | §12 UX |
| P11-FE-030 | Create `frontend/scripts/capture-screenshots.ts` — Playwright script for auto-capturing help center screenshots. Authenticates as a seeded demo account, navigates to each key page, takes named screenshots saved to `frontend/public/docs/screenshots/`. Pages: `dashboard-overview.png`, `reservation-create.png`, `reservation-detail.png`, `sms-logs.png`, `reliability-badge.png`, `widget-page.png`, `waitlist.png`, `customers.png`, `reputation.png`, `leo.png`. Handles: wait for network idle before capture, dark-mode variant skipped (light only). | §12 UX |
| P11-FE-031 | Add `docs-screenshots` target to `Makefile` — runs `docker compose run --rm frontend pnpm tsx scripts/capture-screenshots.ts`. Requires `BASE_URL` env variable (default `http://nginx`). | §12 UX |
| P11-FE-032 | Implement client-side search in `HelpIndexView.vue` — index built from a static `HELP_MODULES` array (title, description, keywords per module). Filter on input using case-insensitive includes. Show "Aucun résultat" empty state. No debounce needed (local data). | §12 UX |

#### 4.18.5 Front-end Tests

| Test File                                                       | Test Cases                                                                                                                                    |
|-----------------------------------------------------------------|-----------------------------------------------------------------------------------------------------------------------------------------------|
| `src/views/help/__tests__/HelpIndexView.test.ts`               | Renders 8 module cards, search filters modules by title, search filters by keyword, "Aucun résultat" shown when no match, back link renders   |
| `src/components/help/__tests__/MermaidDiagram.test.ts`         | Renders without error given a valid mermaid definition, outputs a non-empty SVG node                                                           |
| `src/views/help/__tests__/HelpScoringView.test.ts`             | Renders all 3 score tiers, `MermaidDiagram` receives correct `definition` prop                                                                 |

#### 4.18.6 DevOps / Infrastructure Tasks

| ID         | Task                                                                                | PRD Ref |
|------------|-------------------------------------------------------------------------------------|---------|
| P11-DO-040 | Add `mermaid` to `frontend/package.json` devDependencies (`pnpm add -D mermaid`). Regenerate `pnpm-lock.yaml`. Confirm bundle does not include mermaid in production build (dynamic import inside `MermaidDiagram.vue`). | §12 UX |
| P11-DO-041 | Add `docs-screenshots` target to `Makefile` as described in P11-FE-031. Document the command in `README.md` or inline Makefile comment. | §12 UX |

#### 4.18.7 Deliverables Checklist

- [ ] `/help` accessible without authentication
- [ ] 8 module pages render with text, screenshots, and diagrams
- [ ] Mermaid diagrams render as SVG (not blank) in browser
- [ ] `make docs-screenshots` completes without error and produces PNG files in `frontend/public/docs/screenshots/`
- [ ] Client-side search returns correct results for "réservation", "SMS", "score", "widget"
- [ ] Help center links visible from AppLayout (authenticated) and LandingView (public)
- [ ] All Sprint 18 Vitest tests passing
- [ ] CI pipeline green

---

## Section 5 — API Endpoints Delivered in Phase 11

| Method | Endpoint                               | Controller                    | Auth   | Notes                                                                                          |
|--------|----------------------------------------|-------------------------------|--------|------------------------------------------------------------------------------------------------|
| PATCH  | `/api/v1/business/onboarding-complete` | `BusinessController`          | Bearer | Idempotent — sets `onboarding_completed_at = now()` if null. Returns updated `BusinessResource`. |

*(All help center pages are served as static Vue SPA routes — no new API endpoints beyond the above.)*

---

## Section 6 — Exit Criteria

| # | Criterion                                                                                               | Validated |
|---|---------------------------------------------------------------------------------------------------------|-----------|
| 1 | All functional requirements in Section 3 (Included rows) are implemented and manually verified         | [ ]       |
| 2 | Backend test coverage ≥ 80% (Pest + PHPStan passing)                                                   | [ ]       |
| 3 | Frontend test coverage ≥ 70% (Vitest)                                                                  | [ ]       |
| 4 | `OnboardingTour` shown on a new account's first login and not again after completion                   | [ ]       |
| 5 | All 5 empty states render correctly (verified in browser with empty dataset)                            | [ ]       |
| 6 | `/help` accessible at the public URL without authentication                                             | [ ]       |
| 7 | `make docs-screenshots` runs successfully and produces ≥ 10 PNG files                                  | [ ]       |
| 8 | Mermaid diagrams render as SVG in Chrome and Firefox                                                    | [ ]       |
| 9 | `pnpm lint` and `pnpm format:check` pass without errors                                                 | [ ]       |
| 10| CI pipeline green on `main` after merge                                                                 | [ ]       |

---

## Section 7 — Risks Specific to Phase 11

| Risk                                                               | Probability | Impact | Mitigation                                                                                              |
|--------------------------------------------------------------------|-------------|--------|---------------------------------------------------------------------------------------------------------|
| Tour `getBoundingClientRect` targets wrong element if IDs change   | Medium      | Medium | Use stable semantic IDs (`id="dashboard-stats"`) that are unlikely to change; add snapshot tests for IDs |
| Mermaid bundle size bloat (library is ~2 MB)                       | High        | Low    | Dynamic import in `MermaidDiagram.vue` (`import('mermaid')`) — excluded from main chunk                 |
| Playwright screenshots fail in headless Docker (font/rendering)    | Medium      | Low    | Use `--no-sandbox` flag; accept minor font differences; screenshots are docs-only, not tested in CI      |
| Help content becomes stale after future feature changes            | Medium      | Medium | Add comment banner in each help view: "Update screenshots via `make docs-screenshots` after UI changes" |
| Onboarding tour blocks UX if overlay z-index conflicts with modals | Low         | Medium | Set `z-index: 9999` on tour overlay; test with reservation creation modal open                          |

---

## Section 8 — External Dependencies

| Service/Library     | Phase 11 Usage                                              | Fallback if Unavailable                                  |
|---------------------|-------------------------------------------------------------|----------------------------------------------------------|
| `mermaid` (npm)     | Renders flow diagrams in help center                        | Replace with static PNG images generated offline         |
| `@playwright/test`  | Screenshot capture script (`make docs-screenshots`)         | Manual screenshots — script is optional, not CI-blocking |
| Docker Compose      | Running screenshot capture in isolated container            | Run `pnpm tsx scripts/capture-screenshots.ts` locally    |

---

## Diagrams

### Reservation Lifecycle (for `reservation_lifecycle.mmd`)

```
stateDiagram-v2
    [*] --> pending_verification : Reservation created (unverified phone)
    [*] --> pending_reminder : Reservation created (phone verified)
    pending_verification --> pending_reminder : Client confirms via SMS link
    pending_verification --> cancelled_no_confirmation : Token expired, no action
    pending_reminder --> confirmed : Client confirms reminder
    pending_reminder --> cancelled_by_client : Client cancels via SMS link
    pending_reminder --> no_show : Business marks no-show
    confirmed --> show : Business marks showed up
    confirmed --> no_show : Business marks no-show
    confirmed --> cancelled_by_client : Client cancels (within window)
    show --> [*]
    no_show --> [*]
    cancelled_by_client --> [*]
    cancelled_no_confirmation --> [*]
```

### SMS Pipeline (for `sms_pipeline.mmd`)

```
flowchart TD
    A[Reservation created] --> B{Phone verified?}
    B -- Yes --> C[Score lookup]
    B -- No --> D[SendVerificationSms job]
    D --> E[SMS: confirm link]
    E --> F{Client action?}
    F -- Confirms --> C
    F -- No response, token expired --> G[Status: cancelled_no_confirmation]
    C --> H{Score tier?}
    H -- ≥90%  Reliable --> I[No reminder]
    H -- 70-89% Average --> J[Reminder at -2h]
    H -- <70% At Risk --> K[Reminder at -2h]
    K --> L[Reminder at -30min]
    J --> M{Confirmation received?}
    L --> M
    M -- Yes --> N[Status: confirmed]
    M -- No, -15min passed --> O[Status: cancelled_no_confirmation]
```

### Scoring Model (for `scoring.mmd`)

```
flowchart LR
    A[Phone number] --> B[Lookup cross-business history]
    B --> C{Has history?}
    C -- No --> D[Score = null → treat as At Risk]
    C -- Yes --> E[score = shows / total_reservations]
    E --> F{Score value}
    F -- ≥90% --> G[Reliable — no reminder]
    F -- 70–89% --> H[Average — 1 reminder]
    F -- <70% --> I[At Risk — 2 reminders]
    N[Reservation status changes] --> O[RecalculateReliabilityScore job]
    O --> P[Update customers.reliability_score]
    P --> B
```

---

## Assumptions

> The following assumptions were made during spec generation. Review and adjust before implementation begins.

- `mermaid` npm package is not yet in `package.json` — must be added as a devDependency with dynamic import to keep bundle lean.
- A seeded demo account (`demo@zeronoshow.fr`) with sample data exists for the Playwright screenshot script — create it in `DatabaseSeeder` if not present.
- The `AppLayout.vue` router refactor from Phase 10 (nested routes, `id="page-main"`) is already merged.
- Help center content (text, screenshots) is written in French, consistent with all UI text.
- Screenshots captured by Playwright are committed to the repository under `frontend/public/docs/screenshots/` (not `.gitignored`).
