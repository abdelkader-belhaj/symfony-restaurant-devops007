#!/bin/sh
set -eu

write_runtime_env() {
  cat > .env <<EOF
APP_ENV=${APP_ENV:-prod}
APP_SECRET=${APP_SECRET:-change_me_for_prod}
CORS_ALLOW_ORIGIN=${CORS_ALLOW_ORIGIN:-'^https?://(localhost|127\.0\.0\.1)(:[0-9]+)?$'}
GOOGLE_CLIENT_ID=${GOOGLE_CLIENT_ID:-}
GOOGLE_CLIENT_SECRET=${GOOGLE_CLIENT_SECRET:-}
DATABASE_URL=${DATABASE_URL:-mysql://app:app@db:3306/symfresto07?serverVersion=8.0.32&charset=utf8mb4}
ADMIN_EMAIL=${ADMIN_EMAIL:-admin@restaurant.local}
ADMIN_PASSWORD=${ADMIN_PASSWORD:-admin123}
ADMIN_NAME=${ADMIN_NAME:-Administrateur}
SYMFONY_DEPRECATIONS_HELPER=${SYMFONY_DEPRECATIONS_HELPER:-disabled}
EOF
}

wait_for_database() {
  host="db"
  port="3306"

  if [ -n "${DATABASE_URL:-}" ]; then
    if printf '%s' "$DATABASE_URL" | grep -Eq '@([^/:]+)'; then
      host=$(printf '%s' "$DATABASE_URL" | sed -n 's/.*@\([^/:]*\).*/\1/p')
    fi

    if printf '%s' "$DATABASE_URL" | grep -Eq ':[0-9]+/'; then
      port=$(printf '%s' "$DATABASE_URL" | sed -n 's/.*:\([0-9]*\)\/\{0,1\}.*/\1/p')
    fi
  fi

  export DB_HOST="$host"
  export DB_PORT="$port"

  echo "Waiting for database on ${host}:${port}..."
  until php -r '$host=getenv("DB_HOST") ?: "db"; $port=(int)(getenv("DB_PORT") ?: 3306); $socket=@fsockopen($host, $port, $errno, $errstr, 3); if ($socket) { fclose($socket); exit(0); } exit(1);' >/dev/null 2>&1; do
    sleep 2
  done
}

write_runtime_env
mkdir -p var/cache var/log public/uploads
chown -R www-data:www-data var public/uploads 2>/dev/null || true
chmod -R 775 var public/uploads 2>/dev/null || true
wait_for_database

php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration || true
php bin/console app:create-admin

exec "$@"
