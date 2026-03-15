FROM node:22-alpine

RUN npm install -g pnpm

WORKDIR /app

COPY docker/node/start-frontend.sh /usr/local/bin/start-frontend
RUN chmod +x /usr/local/bin/start-frontend

RUN addgroup -S appgroup && adduser -S appuser -G appgroup
USER appuser
