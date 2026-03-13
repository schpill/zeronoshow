DOCKER_UID := $(shell id -u)
DOCKER_GID := $(shell id -g)
DOCKER_COMPOSE ?= $(shell if docker compose version > /dev/null 2>&1; then echo "docker compose"; else echo "docker-compose"; fi)

PROD_HOST := gerald@<PROD_IP>
PROD_DIR  := /home/gerald/web/zeronoshow

.PHONY: up down restart build install test lint fresh seed \
        shell-api shell-frontend tinker \
        test-be test-fe \
        go-live

# ─── Local dev ────────────────────────────────────────────────────────────────

up:
	$(DOCKER_COMPOSE) up -d

upbuild:
	$(DOCKER_COMPOSE) up -d --build

down:
	$(DOCKER_COMPOSE) down

restart:
	$(DOCKER_COMPOSE) restart

build:
	$(DOCKER_COMPOSE) build

install:
	$(DOCKER_COMPOSE) run --rm api composer install
	$(DOCKER_COMPOSE) run --rm frontend pnpm install

# ─── Tests & Lint ─────────────────────────────────────────────────────────────

test:
	$(MAKE) test-be
	$(MAKE) test-fe

test-be:
	$(DOCKER_COMPOSE) run --rm api ./vendor/bin/pint --test
	$(DOCKER_COMPOSE) run --rm api ./vendor/bin/phpstan analyse -c phpstan.neon --memory-limit=512M
	$(DOCKER_COMPOSE) run --rm api ./vendor/bin/pest --stop-on-failure

test-fe:
	$(DOCKER_COMPOSE) run --rm frontend pnpm vitest run --coverage

lint:
	$(DOCKER_COMPOSE) run --rm api ./vendor/bin/pint --test
	$(DOCKER_COMPOSE) run --rm api ./vendor/bin/phpstan analyse -c phpstan.neon --memory-limit=512M
	$(DOCKER_COMPOSE) run --rm frontend pnpm lint
	$(DOCKER_COMPOSE) run --rm frontend pnpm format:check

# ─── Database ─────────────────────────────────────────────────────────────────

fresh:
	$(DOCKER_COMPOSE) run --rm api php artisan migrate:fresh

seed:
	$(DOCKER_COMPOSE) run --rm api php artisan db:seed

# ─── Shells ───────────────────────────────────────────────────────────────────

shell-api:
	$(DOCKER_COMPOSE) exec api bash

shell-frontend:
	$(DOCKER_COMPOSE) exec frontend sh

tinker:
	$(DOCKER_COMPOSE) run --rm api php artisan tinker

# ─── Production deploy ────────────────────────────────────────────────────────

go-live:
	@echo "→ Syncing to $(PROD_HOST):$(PROD_DIR) ..."
	rsync -avz --delete \
		--exclude='.git/' \
		--exclude='backend/vendor/' \
		--exclude='backend/storage/logs/*.log' \
		--exclude='backend/.env' \
		--exclude='frontend/node_modules/' \
		--exclude='frontend/dist/' \
		--exclude='.env*' \
		-e "ssh -o StrictHostKeyChecking=no" \
		. $(PROD_HOST):$(PROD_DIR)/
	@echo "→ Running post-deploy on server ..."
	ssh -o StrictHostKeyChecking=no $(PROD_HOST) 'cd $(PROD_DIR) && \
		DOCKER_UID=$$(id -u) DOCKER_GID=$$(id -g) \
		$(DOCKER_COMPOSE) -f docker-compose.prod.yml run --rm api composer install --no-dev --optimize-autoloader && \
		$(DOCKER_COMPOSE) -f docker-compose.prod.yml run --rm api php artisan migrate --force && \
		$(DOCKER_COMPOSE) -f docker-compose.prod.yml run --rm api php artisan config:cache && \
		$(DOCKER_COMPOSE) -f docker-compose.prod.yml run --rm api php artisan route:cache && \
		$(DOCKER_COMPOSE) -f docker-compose.prod.yml run --rm api php artisan view:cache && \
		$(DOCKER_COMPOSE) -f docker-compose.prod.yml run --rm api php artisan event:cache && \
		$(DOCKER_COMPOSE) -f docker-compose.prod.yml run --rm frontend pnpm install --frozen-lockfile && \
		$(DOCKER_COMPOSE) -f docker-compose.prod.yml run --rm frontend pnpm build && \
		$(DOCKER_COMPOSE) -f docker-compose.prod.yml up -d --remove-orphans'
	@echo "✓ Deployment complete."
