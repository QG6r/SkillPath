# SkillPath demo image: `docker compose up --build` gives a running site on http://localhost:8000
# with the database migrated and the demo accounts and course seeded.

# --- PHP base with Composer -------------------------------------------------------------------
FROM php:8.3-cli AS php-base
RUN apt-get update \
    && apt-get install -y --no-install-recommends unzip git \
    && rm -rf /var/lib/apt/lists/*
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /app

# --- PHP dependencies -------------------------------------------------------------------------
FROM php-base AS vendor
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --no-progress --no-scripts --prefer-dist

# --- Front end (Vite needs the Ziggy JS that ships inside vendor/) ----------------------------
FROM node:22-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY vite.config.js postcss.config.js tailwind.config.js jsconfig.json ./
COPY resources ./resources
COPY public ./public
COPY --from=vendor /app/vendor ./vendor
RUN npm run build

# --- Runtime ----------------------------------------------------------------------------------
FROM php-base AS app
COPY --from=vendor /app/vendor ./vendor
COPY . .
COPY --from=assets /app/public/build ./public/build

RUN composer dump-autoload --optimize --no-dev --no-scripts \
    && mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache /data \
    && chmod +x docker/entrypoint.sh

ENV DB_CONNECTION=sqlite \
    DB_DATABASE=/data/database.sqlite

EXPOSE 8000
ENTRYPOINT ["sh", "/app/docker/entrypoint.sh"]
