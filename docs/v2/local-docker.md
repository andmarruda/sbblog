# Running SBBlog locally with Docker

```bash
docker compose up -d --build     # first run: installs dependencies, migrates and seeds (1–2 min)
docker compose logs -f app       # wait for "SBBlog is ready on http://localhost:8000"
```

| What | Where |
|---|---|
| Console | http://localhost:8000 (`test@example.com` / `password`, instance admin; also `editor@example.com`) |
| Vite (hot reload) | http://localhost:5173. Edits in `resources/js` show up at once |
| Mailpit (password resets, invitations) | http://localhost:8025 |
| Postgres | `localhost:5434`, database, user `sbblog`, password `secret` |

The project folder is mounted into the containers, so PHP and React edits apply without a
rebuild. The `queue` and `scheduler` services run the queue worker (image variants, webhooks)
and the scheduler (scheduled publishing, snapshots). Restart the queue after you change a job:
`docker compose restart queue`.

## Everyday commands

```bash
docker compose exec app php artisan migrate
docker compose exec app php artisan tinker
docker compose exec app composer require vendor/package
docker compose exec vite pnpm add some-package
docker compose exec app php artisan migrate:fresh --seed     # start over with demo data
docker compose down        # stop (keeps the database)
docker compose down -v     # stop and delete the database
```

Ports can be changed with `APP_PORT`, `VITE_PORT`, `DB_FORWARD_PORT` and `MAILPIT_PORT`.
Containers run with your uid (1000 by default; set `UID`/`GID` otherwise), so files they
create stay yours. The values under `environment` in `docker-compose.yml` override `.env`, so
the same `.env` keeps working outside Docker.

## Tests

`./docker/test.sh` runs the suite inside the container, on its own `sbblog_testing` database
(created on the first start); it takes the usual options, e.g. `./docker/test.sh --filter=Themes`.
Running `php artisan test` on the host still works as before.
