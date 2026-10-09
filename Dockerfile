# syntax=docker/dockerfile:1.9-labs
# (-labs only for `COPY --exclude`, which is not in the stable 1.9 channel.)
#
# CatatanCakadi — FrankenPHP image.
#
# Scaled down from the Batamtix pattern (~/projects/php/batamtix) for what
# this app actually needs: no Reverb, no Varnish, no ClamAV, no queue
# workers. Every stage descends from `base`, so an extension compiled once
# exists everywhere.
#
#   base                FrankenPHP + the PHP extensions this app uses.
#   vendor-production / vendor-testing
#                       Composer dependencies resolved from the lockfile
#                       only, so a source-only commit reuses this layer.
#   testing             base + vendor-testing + source, runs Pest against
#                       sqlite :memory: (see phpunit.xml). Build-time only.
#   frontend-base       base tuned for the Vite/Wayfinder build (needs a
#                       much higher memory_limit than serving a request does).
#   assets              frontend-base + Bun + source, runs `bun run build:ssr`
#                       (client bundle in public/build, SSR bundle in
#                       bootstrap/ssr).
#   production          base + source + vendor + compiled assets + Bun (to
#                       run the Inertia SSR bundle alongside FrankenPHP).

ARG FRANKENPHP_VERSION=1.12.6
ARG PHP_VERSION=8.4
ARG COMPOSER_VERSION=2
ARG BUN_VERSION=1.4.2

FROM composer:${COMPOSER_VERSION} AS composer-bin

# Taken from the official Bun image rather than the install script, so the
# version is pinned explicitly. A dedicated stage (rather than `COPY --from=
# oven/bun:...` directly) so BuildKit classifies it as a build stage at
# parse time using the top-level ARG default — a `--target` build that
# doesn't reach the `assets` stage never instantiates a re-declared ARG
# there, leaving `${BUN_VERSION}` unresolved and the external-image
# reference invalid.
FROM oven/bun:${BUN_VERSION}-alpine AS bun-bin


# ---------------------------------------------------------------------------
# base — FrankenPHP + the extensions this app needs
# ---------------------------------------------------------------------------
FROM dunglas/frankenphp:${FRANKENPHP_VERSION}-php${PHP_VERSION}-alpine AS base

# Bind mounts (dev) and the storage/app volume (prod) carry the host's
# ownership through into the container, so this matches the deploy host's
# usual uid/gid.
ARG UID=1000
ARG GID=1000

# bash: composer/artisan scripts; curl: compose healthchecks. Neither ships
# in the Alpine base image.
RUN apk add --no-cache bash curl

# gd        — Intervention Image's fallback driver (app/Providers/AppServiceProvider.php
#             uses imagick only if it happens to be loaded, otherwise gd)
# intl      — Laravel locale/number formatting
# bcmath, exif, zip, pcntl — framework defaults / signal handling
# redis     — cache/session/rate-limiter store (REDIS_CLIENT=phpredis)
# sodium, opcache, pdo_sqlite (production database), posix are already in the FrankenPHP base image.
RUN install-php-extensions \
    gd \
    intl \
    bcmath \
    exif \
    zip \
    pcntl \
    redis

COPY --from=composer-bin /usr/bin/composer /usr/local/bin/composer

# /data and /config are Caddy's XDG directories; it provisions a local CA
# into /data/caddy on startup even when only serving plain HTTP, so they must
# be writable by the unprivileged user.
RUN set -eux; \
    if ! getent group "${GID}" >/dev/null; then addgroup -g "${GID}" app; fi; \
    if ! getent passwd "${UID}" >/dev/null; then \
        adduser -D -u "${UID}" -G "$(getent group "${GID}" | cut -d: -f1)" -s /bin/bash app; \
    fi; \
    install -d -o "${UID}" -g "${GID}" /app; \
    chown -R "${UID}:${GID}" /data /config

WORKDIR /app

# A bare ":port" tells Caddy to serve plain HTTP and skip certificate
# provisioning — TLS is terminated by the host's Nginx in front of us.
ENV SERVER_NAME=":8000" \
    SERVER_ROOT="public/" \
    COMPOSER_ALLOW_SUPERUSER=1


# ---------------------------------------------------------------------------
# vendor-production / vendor-testing — dependencies from the lockfile
# ---------------------------------------------------------------------------
FROM base AS vendor-production

COPY composer.json composer.lock ./
RUN --mount=type=cache,target=/tmp/composer-cache,sharing=locked \
    COMPOSER_CACHE_DIR=/tmp/composer-cache \
    composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction

# Separate from vendor-production rather than derived from it: `composer
# install` prunes as well as installs, so layering dev deps on a --no-dev
# tree is not a smaller operation, just a less cacheable one.
FROM base AS vendor-testing

COPY composer.json composer.lock ./
RUN --mount=type=cache,target=/tmp/composer-cache,sharing=locked \
    COMPOSER_CACHE_DIR=/tmp/composer-cache \
    composer install --no-scripts --no-autoloader --prefer-dist --no-interaction


# ---------------------------------------------------------------------------
# testing — Pest on the production PHP runtime
# ---------------------------------------------------------------------------
# Build-time only, not in production's ancestry — a separate `--target
# testing` build (see the Jenkinsfile), so a build with tests skipped still
# shares every dependency layer through the cache.
FROM base AS testing

COPY --from=vendor-testing /app/vendor ./vendor
COPY . .

# phpunit.xml pins DB_CONNECTION to sqlite :memory:, so no external database
# is needed here; every Paratest worker gets its own in-memory database, so
# --parallel is safe and cuts the suite to roughly the slowest worker.
RUN set -eux; \
    mkdir -p storage/framework/views storage/framework/cache/data \
        storage/framework/sessions storage/app/public storage/app/private \
        storage/logs bootstrap/cache; \
    cp .env.example .env; \
    composer dump-autoload; \
    php artisan key:generate --force; \
    php artisan config:clear; \
    php artisan test --compact --parallel


# ---------------------------------------------------------------------------
# frontend-base — base tuned for the Vite/Wayfinder build
# ---------------------------------------------------------------------------
FROM base AS frontend-base

# The Wayfinder Vite plugin runs `php artisan wayfinder:generate` during the
# build, which reflects over every route and controller at once and needs
# well over PHP's default 128M. This target never serves a request, so an
# unbounded limit here has no production consequence.
RUN printf 'memory_limit = -1\n' > "$PHP_INI_DIR/conf.d/zz-build.ini"


# ---------------------------------------------------------------------------
# frontend-source — JS deps, PHP vendor, source and .env shared by the
# `assets` build and the `frontend-testing` run, so both reuse these layers
# ---------------------------------------------------------------------------
FROM frontend-base AS frontend-source

ARG UID=1000
ARG GID=1000

COPY --from=bun-bin /usr/local/bin/bun /usr/local/bin/bun
RUN apk add --no-cache libstdc++ libgcc
RUN ln -s /usr/local/bin/bun /usr/local/bin/bunx && bun --version

# JS deps first, ahead of anything PHP — the two trees are independent, and
# this ordering stops a composer.lock change from invalidating `bun install`.
COPY package.json bun.lock ./
RUN --mount=type=cache,target=/tmp/bun-cache,sharing=locked \
    BUN_INSTALL_CACHE_DIR=/tmp/bun-cache \
    bun install --frozen-lockfile --ignore-scripts

# vendor/ is architecture-independent pure PHP, resolved once in
# vendor-production and copied here rather than installed a second time.
COPY composer.json composer.lock ./
COPY --from=vendor-production /app/vendor ./vendor

# The PHP test tree and phpunit.xml play no part in the frontend build or in
# Vitest, so they are left out of this layer: editing a Pest test no longer
# invalidates `bun run build:ssr` or the Vitest run.
COPY --exclude=tests --exclude=phpunit.xml . .

# The Wayfinder plugin boots the framework during the build, so it needs an
# .env and a key even though nothing here touches a database. No VITE_* build
# args are needed (unlike Batamtix's Reverb setup) — this app has nothing
# compiled into the bundle beyond VITE_APP_NAME from .env.example.
RUN set -eux; \
    mkdir -p storage/framework/views storage/framework/cache/data \
        storage/framework/sessions storage/logs bootstrap/cache; \
    cp .env.example .env; \
    composer dump-autoload; \
    php artisan key:generate


# ---------------------------------------------------------------------------
# assets — build-time only: compiles public/build
# ---------------------------------------------------------------------------
FROM frontend-source AS assets

RUN bun run build:ssr


# ---------------------------------------------------------------------------
# frontend-testing — Vitest on top of frontend-source (deps, source, .env).
# Branches off before the production bundle so tests never wait on, or
# invalidate, `build:ssr`; only the Wayfinder types the tests import are
# generated. Built only by `--target frontend-testing`.
# ---------------------------------------------------------------------------
FROM frontend-source AS frontend-testing

# Vitest's jsdom environment breaks under Bun's runtime (the image has no
# Node), so install Node for the test workers.
RUN apk add --no-cache nodejs

RUN php artisan wayfinder:generate && bun run test


# ---------------------------------------------------------------------------
# production — source baked in, no frontend runtime or dev dependencies
# ---------------------------------------------------------------------------
FROM base AS production

ARG UID=1000
ARG GID=1000

# Runs the compiled Inertia SSR bundle (bootstrap/ssr) alongside FrankenPHP —
# see the entrypoint below. Bun, not Node: it's the only JS runtime this
# image otherwise touches (the `assets` stage builds with it too), so this
# avoids carrying two JS runtimes into production for one small process.
COPY --from=bun-bin /usr/local/bin/bun /usr/local/bin/bun
RUN apk add --no-cache libstdc++ libgcc && \
    ln -s /usr/local/bin/bun /usr/local/bin/bunx && bun --version

RUN set -eux; \
    mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"; \
    printf '%s\n' \
        'expose_php = off' \
        'memory_limit = 256M' \
        'upload_max_filesize = 32M' \
        'post_max_size = 32M' \
        'max_execution_time = 60' \
        'opcache.enable = 1' \
        'opcache.validate_timestamps = 0' \
        'opcache.memory_consumption = 128' \
        'opcache.max_accelerated_files = 20000' \
        'opcache.interned_strings_buffer = 16' \
        'realpath_cache_size = 4096K' \
        'realpath_cache_ttl = 600' \
        > "$PHP_INI_DIR/conf.d/zz-catatancakadi.ini"

# Tests are excluded at copy time instead of deleted afterwards, so they never
# enter a layer (and editing one does not invalidate this stage's cache).
COPY --chown=${UID}:${GID} --exclude=tests --exclude=phpunit.xml . .
COPY --from=vendor-production --chown=${UID}:${GID} /app/vendor ./vendor
COPY --from=assets --chown=${UID}:${GID} /app/public/build ./public/build
COPY --from=assets --chown=${UID}:${GID} /app/bootstrap/ssr ./bootstrap/ssr

# storage/app and storage/database are bind-mounted at run time (uploads and
# the SQLite file must survive a deploy and be shared by both blue and green); everything else is per-container and
# recreated empty here — Laravel expects these directories to exist and will
# not create them itself.
#
# config:cache/route:cache are NOT built here: they would bake in this build
# agent's environment rather than the container's real one. They run in the
# entrypoint instead, against whatever env docker-compose actually passes in.
RUN set -eux; \
    mkdir -p storage/framework/views storage/framework/cache/data \
        storage/framework/sessions storage/app/public storage/app/private \
        storage/logs bootstrap/cache; \
    cp .env.example .env; \
    composer dump-autoload --no-dev --optimize --no-interaction; \
    php artisan view:cache; \
    php artisan event:cache; \
    rm -f .env; \
    chown -R "${UID}:${GID}" storage bootstrap/cache vendor

COPY <<'SH' /usr/local/bin/docker-entrypoint
#!/bin/sh
set -eu

# No .env file is in the image — docker-compose passes the real environment
# directly, and Laravel prefers real env vars over a dotenv file, so
# config:cache resolves against exactly what this container was started with.
#
# Migrations run here rather than as a separate deploy step: the database is
# shared between blue and green, so this must stay backward-compatible for
# the length of one deployment (the old colour keeps serving during the new
# one's health-check window).
#
# APP_KEY is validated explicitly because config:cache does NOT fail when it
# is empty (the encrypter is resolved lazily) — the container would otherwise
# start, pass /up, and only blow up with a confusing "headers already sent"
# cascade on the first request that touches encrypted sessions (e.g. /login).
if [ -z "${APP_KEY:-}" ]; then
    echo "!! APP_KEY is empty/unset — refusing to start." >&2
    exit 1
fi
php artisan config:cache
php artisan route:cache
php artisan storage:link || true
# SQLite creates the file but not its directory; touch makes a first deploy
# (empty bind mount) work.
if [ "${DB_CONNECTION:-}" = "sqlite" ] && [ -n "${DB_DATABASE:-}" ]; then
    mkdir -p "$(dirname "$DB_DATABASE")"
    touch "$DB_DATABASE"
fi
php artisan migrate --force

# Inertia SSR: `inertia:start-ssr` runs bun against bootstrap/ssr and blocks
# in the foreground, so it's backed by a restart loop and pushed to the
# background instead. This is best-effort — Inertia's HttpGateway falls back
# to client-side rendering whenever INERTIA_SSR_URL is unreachable — so a
# crash loop here must never block or take down the main FrankenPHP process.
if [ "${INERTIA_SSR_ENABLED:-true}" != "false" ]; then
    (
        while true; do
            php artisan inertia:start-ssr --runtime=bun || true
            sleep 1
        done
    ) &
fi

exec docker-php-entrypoint "$@"
SH
RUN chmod +x /usr/local/bin/docker-entrypoint

USER ${UID}:${GID}

ENTRYPOINT ["docker-entrypoint"]
# Inherited from the FrankenPHP base image, restated because ENTRYPOINT above
# replaces the base image's own and CMD must be re-declared alongside it.
CMD ["frankenphp", "run", "--config", "/etc/frankenphp/Caddyfile"]
