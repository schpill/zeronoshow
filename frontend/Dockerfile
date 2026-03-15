FROM node:22-slim

RUN corepack enable && corepack prepare pnpm@latest --activate

# Shared Playwright browser cache accessible by all users
ENV PLAYWRIGHT_BROWSERS_PATH=/ms-playwright

WORKDIR /app

COPY start-frontend.sh /usr/local/bin/start-frontend
RUN chmod +x /usr/local/bin/start-frontend

# Install Playwright + Chromium + system deps at build time (needs root for apt)
# The frontend directory is volume-mounted at runtime, so pnpm install runs there.
# At build time we install just to cache the apt libs and browser binary in the image.
COPY package.json ./
RUN pnpm add -D playwright && pnpm exec playwright install --with-deps chromium
RUN chmod -R 755 /ms-playwright
RUN rm -rf node_modules /root/.cache/ms-playwright

RUN addgroup --system appgroup && adduser --system --ingroup appgroup appgroup
USER appuser
