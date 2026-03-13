# Phase 4 Production Environment Guide

## Scope

This document lists every production variable expected by the Phase 4 deployment assets, where it comes from, and how it is used. Store real values in the server `.env`, Docker host environment, or your deployment secret store. Do not commit secrets.

## Required GitHub Secrets

| Secret | Source | Format | Used By |
| --- | --- | --- | --- |
| `PROD_HOST` | DigitalOcean droplet public IP or DNS name | `203.0.113.10` or `prod.zeronoshow.fr` | `.github/workflows/deploy.yml` |
| `PROD_USER` | Linux deploy user created on the server | `znz` | `.github/workflows/deploy.yml` |
| `PROD_SSH_KEY` | Private SSH key matching the server deploy user | OpenSSH private key block | `.github/workflows/deploy.yml` |
| `PROD_APP_PATH` | Absolute path of the checked-out repo on the server | `/srv/zeronoshow` | `.github/workflows/deploy.yml` |
| `GHCR_TOKEN` | GitHub token or PAT with `read:packages` on the server | token string | `scripts/forge-deploy.sh` on server |

## Required Server Variables

### Application

| Variable | Source | Example | Notes |
| --- | --- | --- | --- |
| `APP_NAME` | static | `ZeroNoShow` | Optional override |
| `APP_ENV` | static | `production` | Must stay `production` |
| `APP_KEY` | generated on server | `base64:...` | Generate with `php artisan key:generate --show` |
| `APP_URL` | domain configuration | `https://zeronoshow.fr` | Must match production URL used for webhooks |
| `APP_TIMEZONE` | product choice | `Europe/Paris` | Default business timezone |
| `APP_LOCALE` | product choice | `fr` | UI locale |

### Database

| Variable | Source | Example | Notes |
| --- | --- | --- | --- |
| `DB_CONNECTION` | static | `pgsql` | Phase 4 assumes PostgreSQL |
| `DB_HOST` | Docker service or managed DB endpoint | `postgres` | Use managed DB host if not local container |
| `DB_PORT` | provider | `5432` | |
| `DB_DATABASE` | generated during provisioning | `zeronoshow` | |
| `DB_USERNAME` | generated during provisioning | `zeronoshow` | |
| `DB_PASSWORD` | generated during provisioning | strong password | |

### Redis / Queue / Cache

| Variable | Source | Example | Notes |
| --- | --- | --- | --- |
| `QUEUE_CONNECTION` | static | `redis` | |
| `CACHE_STORE` | static | `redis` | |
| `REDIS_CLIENT` | static | `phpredis` | |
| `REDIS_HOST` | Docker service or managed Redis endpoint | `redis` | |
| `REDIS_PORT` | provider | `6379` | |
| `REDIS_PASSWORD` | generated during provisioning | strong password | Required by compose |

### Mail

| Variable | Source | Example | Notes |
| --- | --- | --- | --- |
| `MAIL_MAILER` | mail provider | `smtp` | |
| `MAIL_SCHEME` | mail provider | `tls` | |
| `MAIL_HOST` | mail provider | `smtp.mailgun.org` | |
| `MAIL_PORT` | mail provider | `587` | |
| `MAIL_USERNAME` | mail provider | `postmaster@...` | |
| `MAIL_PASSWORD` | mail provider | app password | |
| `MAIL_FROM_ADDRESS` | product choice | `hello@zeronoshow.fr` | |
| `MAIL_FROM_NAME` | product choice | `ZeroNoShow` | |

### Twilio / Stripe / Sentry

| Variable | Source | Example | Notes |
| --- | --- | --- | --- |
| `TWILIO_ACCOUNT_SID` | Twilio Console | `AC...` | |
| `TWILIO_AUTH_TOKEN` | Twilio Console | secret string | |
| `TWILIO_FROM` | Twilio Console | `+33...` | Sender number |
| `TWILIO_WEBHOOK_SECRET` | optional internal secret | random string | Only if app uses it additionally |
| `STRIPE_KEY` | Stripe Dashboard | `pk_live_...` | |
| `STRIPE_SECRET` | Stripe Dashboard | `sk_live_...` | |
| `STRIPE_WEBHOOK_SECRET` | Stripe Dashboard | `whsec_...` | |
| `SENTRY_LARAVEL_DSN` | Sentry project settings | DSN URL | Backend error capture |
| `VITE_SENTRY_DSN` | Sentry project settings | DSN URL | Frontend error capture |
| `SENTRY_TRACES_SAMPLE_RATE` | static choice | `0.1` | Phase 4 default |

### Deployment Image Selection

| Variable | Source | Example | Notes |
| --- | --- | --- | --- |
| `GHCR_OWNER` | GitHub org/user name | `gerald` | Used to resolve GHCR image path |
| `API_IMAGE_TAG` | GitHub Actions | `sha-abcdef123456` | Current backend runtime image tag |
| `FRONTEND_IMAGE_TAG` | GitHub Actions | `sha-abcdef123456` | Built and tracked for parity, not yet consumed by compose |
| `APP_DOMAIN` | DNS | `zeronoshow.fr` | Used by deploy health check |

## Recommended Server `.env`

Store a file such as `/srv/zeronoshow/.env.prod` and export it before running deployment:

```bash
set -a
source /srv/zeronoshow/.env.prod
set +a
```

## Manual Steps Not Automated By Repo

1. Provision the droplet, firewall rules, and DNS records.
2. Create the Sentry project(s) and obtain DSNs.
3. Create the UptimeRobot monitor and alert contacts.
4. Issue the first Let's Encrypt certificate with Certbot.
5. Store `GHCR_TOKEN` securely on the server before the first deploy.
