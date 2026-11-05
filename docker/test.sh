#!/usr/bin/env bash
# Runs the test suite inside the app container: ./docker/test.sh [--filter=...]
# The compose environment (database cache, mail, APP_URL...) is dropped so phpunit.xml's values apply.
exec docker compose exec -T app env \
  -u APP_ENV -u APP_URL -u SESSION_DRIVER -u CACHE_STORE -u QUEUE_CONNECTION \
  -u MAIL_MAILER -u MAIL_HOST -u MAIL_PORT -u SBBLOG_ALLOW_PRIVATE_WEBHOOKS -u DB_DATABASE \
  php artisan test "$@"
