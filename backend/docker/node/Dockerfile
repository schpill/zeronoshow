FROM node:22-slim

RUN corepack enable && corepack prepare pnpm@latest --activate

# Shared Playwright browser cache accessible by all users
ENV PLAYWRIGHT_BROWSERS_PATH=/ms-playwright

WORKDIR /app

COPY docker/node/start-frontend.sh /usr/local/bin/start-frontend
RUN chmod +x /usr/local/bin/start-frontend

# Install Playwright system deps + Chromium at build time (needs root for apt)
# The frontend directory is volume-mounted at runtime, so pnpm install runs there
# At build time we just need to install the deps to get the apt libs and browser binary
COPY package.json ./
RUN pnpm add -D playwright && pnpm exec playwright install --with-deps chromium
# Clean up — runtime start-frontend.sh will re-run pnpm install from volume mount
RUN rm -rf node_modules

RUN addgroup --system appgroup && adduser --system --ingroup appgroup appgroup
USER appuser
