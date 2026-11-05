#!/usr/bin/env bash
# Prepares the project on the first start of the "app" service: dependencies, .env, key,
# migrations and demo data. The other services wait for the ready marker.
set -euo pipefail
cd /var/www/html

READY=storage/framework/docker-ready

if [[ "${SBBLOG_SETUP:-0}" == "1" ]]; then
  rm -f "$READY"

  [[ -f .env ]] || cp .env.example .env
  [[ -f vendor/autoload.php ]] || composer install --no-interaction --prefer-dist
  [[ -d node_modules ]] || pnpm install --frozen-lockfile

  grep -qE '^APP_KEY=.+' .env || php artisan key:generate --force

  until pg_isready -h "${DB_HOST}" -p "${DB_PORT}" -U "${DB_USERNAME}" >/dev/null 2>&1; do
    echo "waiting for postgres..."; sleep 1
  done

  export PGPASSWORD="${DB_PASSWORD}"
  # The test suite uses its own database (phpunit.xml).
  psql -h "${DB_HOST}" -p "${DB_PORT}" -U "${DB_USERNAME}" -d "${DB_DATABASE}" -tAc \
    "select 1 from pg_database where datname = 'sbblog_testing'" | grep -q 1 \
    || createdb -h "${DB_HOST}" -p "${DB_PORT}" -U "${DB_USERNAME}" sbblog_testing

  php artisan migrate --force
  # Reference data is idempotent; demo data only goes into an empty database.
  users="$(psql -h "${DB_HOST}" -p "${DB_PORT}" -U "${DB_USERNAME}" -d "${DB_DATABASE}" -tAc 'select count(*) from users')"
  if [[ "$users" == "0" ]]; then
    php artisan db:seed --force
  else
    php artisan db:seed --class=Database\\Seeders\\ThemeSeeder --force
  fi

  # A relative link works both on the host and inside the container.
  if [[ ! -e public/storage ]] || [[ "$(readlink public/storage)" == /* ]]; then
    rm -f public/storage && ln -s ../storage/app/public public/storage
  fi

  php artisan wayfinder:generate --with-form >/dev/null
  touch "$READY"
  echo "SBBlog is ready on ${APP_URL}"
else
  until [[ -f "$READY" ]]; do sleep 2; done
fi

exec "$@"
