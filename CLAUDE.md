# CLAUDE.md — ZeroNoShow

Smart no-show prevention platform via SMS reliability scoring.
**Stack:** Laravel 12 (PHP 8.3) · Vue 3 + TypeScript · PostgreSQL · Redis · Twilio · Stripe

---

## Project Structure

```
backend/          Laravel 12 API + queue workers + scheduler
frontend/         Vue 3 SPA (pnpm, Vite, Tailwind CSS 3)
docs/phases/      Spec per phase (phase1.md … phase4.md)
docs/dev/         Task tracking per phase (phase1.md … phase4.md)
docs/graphics/    Design system — colors.md, polices.md
architecture.md   System architecture reference
PRD.md            Product requirements
Makefile          All dev/test/deploy commands
docker-compose.yml Local dev environment
```

## Development Commands

Always run via **Make** (delegates to Docker Compose):

```bash
make up           # Start all services (api, worker, scheduler, nginx, db, redis, mailpit)
make down         # Stop all services
make install      # composer install + pnpm install
make test         # Run all tests (backend + frontend)
make test-be      # Pint + PHPStan + Pest (stop on first failure)
make test-fe      # Vitest + coverage
make lint         # Pint + PHPStan + ESLint + Prettier check
make fresh        # php artisan migrate:fresh
make seed         # php artisan db:seed
make shell-api    # Bash into the api container
make tinker       # php artisan tinker
```

## Architecture Principles

- **Modular Monolith** — single Laravel app, no microservices
- **Queue everything async** — SMS sends are never synchronous (SendVerificationSms job)
- **SmsServiceInterface** abstracts Twilio — binding in AppServiceProvider
- **Fail loudly** — all SMS failures logged + captured in Sentry
- **API versioned** at `/api/v1`
- **PostgreSQL** for all persistence, **Redis** for cache (30s TTL on reservation index) and queues

## Key Conventions

### Backend
- PHP 8.3 strict types, Laravel 12 conventions
- Form Requests for all validation (French error messages)
- Policies for authorization (e.g. ReservationPolicy)
- Resources for all API responses (ReservationResource, SmsLogResource)
- UUIDs via `HasUuids` trait on all models
- `TIMESTAMPTZ` columns (never `TIMESTAMP`) with composite indexes
- Tests: **Pest** with Feature tests under `tests/Feature/`, Unit under `tests/Unit/`
- Local tests use SQLite in-memory; CI uses PostgreSQL 16

### Frontend
- **Vue 3 Composition API + TypeScript** (no Options API)
- **Pinia** for state (auth store)
- **Vue Router** with `requiresAuth` / `guestOnly` meta guards
- **Axios** (not native fetch) — configured in `src/api/axios.ts` with request/response interceptors (401 → login, 402 → subscribe)
- All UI text in **French**
- Brand palette + Inter/JetBrains Mono fonts via Tailwind config
- Tests: **Vitest** + Vue Test Utils

### DevOps
- Docker Compose for local dev — services: `api`, `worker`, `scheduler`, `nginx`, `db`, `redis`, `mailpit`
- PHP Dockerfile: `8.3-fpm`, PCOV, Redis extension
- CI: GitHub Actions — two jobs: `backend` (Pint + PHPStan + Pest) and `frontend` (Vitest + coverage)
- No `.env.example` as live env file — use `.env` locally (gitignored)

## Phase Status

| Phase | Name | Status | Spec | Tasks |
|-------|------|--------|------|-------|
| 1 | Foundation — Auth, Reservations & SMS Pipeline | **Complete** (merged 2026-03-13) | docs/phases/phase1.md | docs/dev/phase1.md |
| 2 | Smart Reminders, Scoring & Auto-cancel | **Complete** (merged 2026-03-13) | docs/phases/phase2.md | docs/dev/phase2.md |
| 3 | Analytics Dashboard, Billing & Dark Mode | **Complete** (merged 2026-03-13) | docs/phases/phase3.md | docs/dev/phase3.md |
| 4 | Hardening & Launch | **Complete** (merged 2026-03-13) | docs/phases/phase4.md | docs/dev/phase4.md |

## Team

| Rôle | Personne | Statut |
|------|----------|--------|
| Développeur principal | Pierre | En arrêt maladie (retour à confirmer) |
| Développeur intérimaire | Gerald (propriétaire du projet) | Actif |

## Workflow

- Feature branches: `apex/phase{N}-{feature}`
- PR → code review (all Critical + Important issues fixed) → merge to `main`
- Always read `docs/phases/phaseN.md` and `docs/dev/phaseN.md` before starting a phase
- Always read `docs/graphics/colors.md` and `docs/graphics/polices.md` before writing frontend code
