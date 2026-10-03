#!/usr/bin/env bash
# Blue/Green deployment for catatancakadi (Laravel).
#
# Runs on the deploy host, inside DEPLOY_PATH (where docker-compose.prod.yml
# and .env live). Deploys the newly loaded image to the currently INACTIVE
# colour, health-checks it, stops the old colour, and — ONLY once this app
# already owns the shared Nginx vhost — flips Nginx to the new colour's port.
#
# cakadi.web.id is currently served by a DIFFERENT app: the Nuxt "lombacv" CV
# site (~/projects/nodejs/catatancakadi), deployed from /www/lombacv on this
# same host. This script never touches that site's Nginx file on its own —
# the cutover is a deliberate one-time manual step:
#
#   1. Run this script (via the Jenkins pipeline, or by hand) once. With no
#      .nginx_managed marker yet, it brings ONE colour up and stops there —
#      it does NOT touch Nginx. cat "$DEPLOY_PATH/.active_color" to see which
#      one (the very first run picks green/5021, not blue/5020 — see the
#      current_color/new_color logic below), then confirm it works:
#        curl -fsS http://127.0.0.1:<5020-or-5021>/up
#   2. Manually install deploy/nginx/cakadi.web.id.conf, edit its proxy_pass
#      to match the port from step 1, and confirm the live site in a browser.
#   3. touch "$DEPLOY_PATH/.nginx_managed" — from then on this script also
#      flips Nginx automatically on every deploy, same as it does for lombacv
#      today.
#   4. Only after step 2 is confirmed working should lombacv's own containers
#      be stopped/decommissioned — that is out of scope for this script.
#
# ---------------------------------------------------------------------------
# Subcommands (usage: deploy-bluegreen.sh <deploy_path> <deploy|rotate|rollback>)
# ---------------------------------------------------------------------------
#   deploy   — bring up the currently INACTIVE colour and health-check it.
#              Does NOT touch Nginx and does NOT stop the old colour, so the
#              live site is untouched while Jenkins runs its smoke test
#              against the new colour's port directly.
#   rotate   — flip Nginx to the colour "deploy" just brought up (only if
#              already Nginx-managed, see above), then stop the old colour.
#              Run this only after the smoke test passes.
#   rollback — smoke test failed: stop/remove the new colour, leave the old
#              (still-live) colour untouched, and clear the pending state.
set -euo pipefail

DEPLOY_PATH="${1:?usage: deploy-bluegreen.sh <deploy_path> <deploy|rotate|rollback>}"
ACTION="${2:?usage: deploy-bluegreen.sh <deploy_path> <deploy|rotate|rollback>}"
STATE_FILE="$DEPLOY_PATH/.active_color"
PENDING_FILE="$DEPLOY_PATH/.pending_color"
NGINX_MANAGED_MARKER="$DEPLOY_PATH/.nginx_managed"
NGINX_SITE_FILE="${NGINX_SITE_FILE:-/etc/nginx/sites-available/cakadi.web.id}"
COMPOSE_FILE="docker-compose.prod.yml"
HEALTH_RETRIES=30
HEALTH_INTERVAL=2

cd "$DEPLOY_PATH"

# The Jenkins "catatancakadi-env" secret file can carry CRLF line endings.
# A trailing \r makes an "empty" APP_KEY value non-empty by byte count (so
# our own -z checks below miss it), while Docker's env_file parser treats it
# as truly empty inside the container — hence the key looking fine here but
# still coming up blank at runtime. Normalize before anything reads it.
if [ -f .env ]; then
  sed -i 's/\r$//' .env
fi

current_color="blue"
if [ -f "$STATE_FILE" ]; then
  current_color="$(cat "$STATE_FILE")"
fi

port_for() {
  case "$1" in
    blue)  echo 5020 ;;
    green) echo 5021 ;;
  esac
}

case "$ACTION" in
  deploy)
    ;;
  rotate|rollback)
    if [ ! -f "$PENDING_FILE" ]; then
      echo "!! No pending deploy found ($PENDING_FILE missing) — run 'deploy' first." >&2
      exit 1
    fi
    new_color="$(cat "$PENDING_FILE")"
    app_port="$(port_for "$new_color")"
    ;;
  *)
    echo "!! Unknown action '$ACTION' (expected deploy|rotate|rollback)" >&2
    exit 1
    ;;
esac

if [ "$ACTION" = "rollback" ]; then
  echo "==> Rolling back failed deploy of $new_color."
  docker compose -f "$COMPOSE_FILE" stop -t 0 "catatancakadi-$new_color" 2>/dev/null || true
  docker compose -f "$COMPOSE_FILE" rm -f "catatancakadi-$new_color" 2>/dev/null || true
  rm -f "$PENDING_FILE"
  echo "==> Rollback complete. Active color remains: $current_color"
  exit 0
fi

if [ "$ACTION" = "rotate" ]; then
  if [ -f "$NGINX_MANAGED_MARKER" ]; then
    if [ -f "$NGINX_SITE_FILE" ]; then
      echo "==> Updating Nginx port to $app_port ($new_color)."
      sed -i -E "s#(proxy_pass[[:space:]]+http://127\.0\.0\.1:)[0-9]+([[:space:]]*;)#\1${app_port}\2#" "$NGINX_SITE_FILE"
      if ! grep -qE "proxy_pass[[:space:]]+http://127\.0\.0\.1:${app_port}[[:space:]]*;" "$NGINX_SITE_FILE"; then
        echo "!! sed did not update proxy_pass to port ${app_port} in ${NGINX_SITE_FILE} — refusing to reload Nginx." >&2
        exit 1
      fi
      nginx -t
      systemctl reload nginx
    else
      echo "!! ${NGINX_MANAGED_MARKER} exists but ${NGINX_SITE_FILE} is missing — skipping Nginx flip." >&2
    fi
  else
    echo "==> ${NGINX_MANAGED_MARKER} not present — this app does not own the shared Nginx vhost yet."
    echo "    See the header of this script for the one-time cutover steps."
  fi

  echo "$new_color" > "$STATE_FILE"
  rm -f "$PENDING_FILE"

  echo "==> Force-stopping old color: $current_color"
  old_container="catatancakadi-$current_color"
  if [ -n "$(docker compose -f "$COMPOSE_FILE" ps -q "$old_container" 2>/dev/null)" ]; then
    docker compose -f "$COMPOSE_FILE" stop -t 0 "$old_container"
    docker compose -f "$COMPOSE_FILE" rm -f "$old_container"
  fi

  echo "==> Rotation complete. Active color is now: $new_color"
  exit 0
fi

# ACTION = deploy
if [ "$current_color" = "blue" ]; then
  new_color="green"
else
  new_color="blue"
fi
app_port="$(port_for "$new_color")"

echo "==> Current active color: $current_color"
echo "==> Deploying new color:  $new_color (app=$app_port)"

# .env is scp'd fresh from the Jenkins "catatancakadi-env" secret on every
# deploy, which can silently reset APP_KEY to empty if that secret was never
# populated. APP_KEY must not be regenerated on every deploy though: blue and
# green share this .env, so a new random key on every run would invalidate
# every encrypted session/cookie on each colour flip. Instead, persist the
# key separately in $APP_KEY_FILE — outside anything Jenkins overwrites —
# and use it to backfill .env whenever the incoming .env has none.
APP_KEY_FILE="$DEPLOY_PATH/.app_key"
current_key="$(grep -E '^APP_KEY=' .env 2>/dev/null | cut -d= -f2- | tr -d '\r')"

if [ -z "$current_key" ]; then
  persisted_key="$(tr -d '\r\n' < "$APP_KEY_FILE" 2>/dev/null || true)"

  case "$persisted_key" in
    base64:*)
      echo "==> APP_KEY missing from .env — restoring the persisted key from $APP_KEY_FILE."
      current_key="$persisted_key"
      ;;
    *)
      echo "==> APP_KEY missing from .env and no valid persisted key found — generating one."
      current_key="base64:$(openssl rand -base64 32)"
      ;;
  esac

  if grep -qE '^APP_KEY=' .env 2>/dev/null; then
    sed -i -E "s#^APP_KEY=.*#APP_KEY=${current_key}#" .env
  else
    printf 'APP_KEY=%s\n' "$current_key" >> .env
  fi
fi

# Keep the persisted copy in sync so a future deploy can recover even if the
# Jenkins secret stays empty. Not a substitute for fixing that secret —
# rotate it there too (see app:rotate-app-key) so it isn't reset on the next
# deploy that DOES carry a (stale) key.
printf '%s' "$current_key" > "$APP_KEY_FILE"
chmod 600 "$APP_KEY_FILE"

# docker-compose.prod.yml declares this network external (fixed IPs require a
# user-defined network, unlike the default "bridge") — create it here so a
# fresh host doesn't need a manual one-time step remembered before the first
# deploy. Must not overlap lombacv-net (172.21.0.0/16) or mongo-net
# (172.20.0.0/24) — check `docker network ls` if this ever needs to change.
# --gateway is pinned explicitly (not left to Docker's default-first-address
# behaviour) so 172.22.0.1 is guaranteed stable across recreations — the
# compose file's REDIS_HOST and extra_hosts entries hardcode that address.
docker network inspect catatancakadi-net >/dev/null 2>&1 || \
  docker network create catatancakadi-net --subnet 172.22.0.0/16 --gateway 172.22.0.1

# Uploads and the SQLite database must survive both the container and the
# colour switch (storage/database is bind-mounted and editable from the host). Owned by
# 1000:1000 to match the container's unprivileged user (the Dockerfile's
# default UID/GID) — Docker would otherwise create these root-owned on first
# run (this script runs as root over SSH), and the container user could never
# write to them.
mkdir -p storage/app storage/logs storage/database
chown -R 1000:1000 storage/app storage/logs storage/database

docker compose -f "$COMPOSE_FILE" --profile "$new_color" up -d --force-recreate

echo "==> Waiting for $new_color to become healthy..."
healthy=0
for _ in $(seq 1 "$HEALTH_RETRIES"); do
  if curl -fsS "http://127.0.0.1:${app_port}/up" >/dev/null 2>&1; then
    healthy=1
    break
  fi
  sleep "$HEALTH_INTERVAL"
done

if [ "$healthy" -ne 1 ]; then
  echo "!! Health check failed for $new_color, rolling back deploy." >&2
  docker compose -f "$COMPOSE_FILE" --profile "$new_color" logs --tail=100 || true
  docker compose -f "$COMPOSE_FILE" stop "catatancakadi-$new_color"
  docker compose -f "$COMPOSE_FILE" rm -f "catatancakadi-$new_color"
  exit 1
fi

echo "==> $new_color is healthy."
echo "$new_color" > "$PENDING_FILE"

if [ ! -f "$NGINX_MANAGED_MARKER" ]; then
  echo "    ${NGINX_MANAGED_MARKER} not present — this app does not own the shared Nginx vhost yet."
  echo "    See the header of this script for the one-time cutover steps."
fi

echo "==> Deploy complete. $new_color is up on port ${app_port}, old color ($current_color) still live."
echo "    Smoke-test http://127.0.0.1:${app_port}/ , then run: $0 $DEPLOY_PATH rotate"
echo "    (or: $0 $DEPLOY_PATH rollback  if the smoke test fails)"
