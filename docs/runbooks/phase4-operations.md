# Phase 4 Operations Runbook

## Purpose

This runbook covers the manual operations that cannot be completed from the repository alone: server provisioning, TLS bootstrap, monitoring, backups, and pilot launch checks.

## 1. Server Provisioning

1. Create a DigitalOcean droplet: Ubuntu 24.04, 2 vCPU, 4 GB RAM.
2. Install Docker Engine and Docker Compose v2.
3. Create a deploy user:
   ```bash
   sudo adduser --disabled-password --gecos "" znz
   sudo usermod -aG docker znz
   sudo mkdir -p /home/znz/.ssh
   sudo chmod 700 /home/znz/.ssh
   ```
4. Add the public key matching `PROD_SSH_KEY` to `/home/znz/.ssh/authorized_keys`.
5. Open ports `22`, `80`, and `443` in UFW:
   ```bash
   sudo ufw allow 22/tcp
   sudo ufw allow 80/tcp
   sudo ufw allow 443/tcp
   sudo ufw enable
   ```
6. Clone the repository to the path stored in `PROD_APP_PATH`.

## 2. First-Time TLS Bootstrap

1. Point `zeronoshow.fr` and `www.zeronoshow.fr` to the droplet IP.
2. Start only nginx with the ACME challenge volume available:
   ```bash
   docker compose -f docker-compose.prod.yml up -d nginx
   ```
3. Run Certbot on the host:
   ```bash
   sudo certbot certonly --webroot \
     -w /var/lib/docker/volumes/zeronoshow_certbot_www/_data \
     -d zeronoshow.fr -d www.zeronoshow.fr
   ```
4. Configure renewal:
   ```bash
   echo '15 3 * * * root certbot renew --quiet && docker compose -f /srv/zeronoshow/docker-compose.prod.yml restart nginx' | sudo tee /etc/cron.d/zeronoshow-certbot
   ```
5. Verify:
   ```bash
   curl -I https://zeronoshow.fr
   ```

## 3. Deployment Procedure

1. Export the variables from `.env.prod`.
2. Ensure `GHCR_TOKEN` is available on the server.
3. Trigger the GitHub Actions `Deploy` workflow or run manually:
   ```bash
   ./scripts/forge-deploy.sh deploy
   ```
4. Validate:
   ```bash
   curl -fsS https://zeronoshow.fr/api/v1/health
   docker compose -f docker-compose.prod.yml ps
   ```
5. If health fails after deployment:
   ```bash
   ./scripts/forge-deploy.sh rollback
   ```

## 4. Sentry Setup

1. Create one Laravel project and one Vue/browser project in Sentry.
2. Copy DSNs into `SENTRY_LARAVEL_DSN` and `VITE_SENTRY_DSN`.
3. After deploy, test backend capture:
   ```bash
   docker compose -f docker-compose.prod.yml exec api php artisan tinker
   >>> throw new \Exception('Sentry test');
   ```
4. Confirm receipt in Sentry within 30 seconds.
5. Add release and environment filters for `production`.

## 5. UptimeRobot Setup

1. Create an HTTP monitor on `https://zeronoshow.fr/api/v1/health`.
2. Interval: 60 seconds.
3. Alert contacts: email plus SMS for on-call owner.
4. Trigger after 3 consecutive failures.
5. Publish the public status page URL for pilot businesses if desired.

## 6. PostgreSQL Backups To DigitalOcean Spaces

1. Install `rclone` or `s3cmd` on the server.
2. Create a Spaces bucket dedicated to backups.
3. Create `/usr/local/bin/zeronoshow-pg-backup.sh`:
   ```bash
   #!/usr/bin/env bash
   set -euo pipefail
   timestamp="$(date +%F-%H%M%S)"
   dump_file="/tmp/zeronoshow-${timestamp}.sql.gz"
   pg_dump "${DB_CONNECTION_STRING}" | gzip > "${dump_file}"
   rclone copy "${dump_file}" "do-spaces:zeronoshow-backups/"
   find /tmp -name 'zeronoshow-*.sql.gz' -mtime +1 -delete
   ```
4. Add a daily cron at 02:30.
5. Test restore on staging before pilot launch.

## 7. Post-Deploy Checklist

1. `curl -w "%{time_total}\n" -o /dev/null -s https://zeronoshow.fr/api/v1/health` stays under `0.100`.
2. Security headers score is `A` or better on `securityheaders.com`.
3. Sentry receives a manual test exception.
4. UptimeRobot monitor is green.
5. `php artisan smoke:test` passes in the production container.
6. Backup upload completes successfully.

## 8. Human Review Required

- Confirm whether production will use containerized PostgreSQL/Redis or managed DigitalOcean services; `docker-compose.prod.yml` currently supports local containers by default.
- Confirm whether the frontend image should become a runtime dependency or remain an archived build artifact; current deployment keeps server-side `pnpm build` as the source of truth.
- Confirm the exact deploy user, path, and GitHub package authentication method before first rollout.
