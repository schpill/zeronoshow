## Integration Sweep

Verified:
- `make swagger` ✅
- `make routes` ✅
- `docker compose run --rm api sh -lc 'APP_ENV=testing DB_CONNECTION=sqlite DB_DATABASE=:memory: CACHE_STORE=array QUEUE_CONNECTION=sync php artisan test --coverage --min=80'` ✅ (`84.3%` total)
- `docker compose run --rm frontend pnpm vitest run --coverage` ✅ (`79.48%` statements / `79.92%` lines)
- `docker compose run --rm api ./vendor/bin/pint --test` ✅
- `docker compose run --rm api ./vendor/bin/phpstan analyse -c phpstan.neon --memory-limit=512M` ✅
- `docker compose run --rm frontend pnpm lint` ✅
- `docker compose run --rm frontend pnpm exec prettier --check .` ✅
- `docker compose run --rm frontend pnpm type-check` ✅

Notes:
- `make routes` required a Makefile compatibility fix for Laravel 12 because `route:list --columns=...` is no longer supported.
- Full route listing confirms `/api/docs`, `/docs`, and all Phase 12 admin endpoints are registered.
- Follow-up integration fixes closed the remaining frontend gaps on impersonation bootstrap, business detail actions/history, and audit filtering/pagination.
