# Deploy on a PaaS (Railway, Koyeb, Northflank, Render)

The Dockerfile's final `paas` stage runs **Nginx + PHP-FPM in one container**
and listens on the port injected by the platform (`$PORT`), so it works on any
Docker-based PaaS without extra configuration. The database is always an
external/managed MySQL instance.

Requirements are set as platform environment variables — there is no `.env`
file inside the container.

| Variable | Value |
|---|---|
| `APP_NAME` | `Leave Portal` |
| `APP_ENV` | `production` |
| `APP_DEBUG` | `false` |
| `APP_KEY` | output of `php artisan key:generate --show` (run it locally) |
| `APP_URL` | the public HTTPS URL of the deployment |
| `LOG_CHANNEL` | `stderr` |
| `LOG_LEVEL` | `warning` |
| `RUN_MIGRATIONS` | `1` (runs `php artisan migrate --force` on deploy) |
| `DB_HOST` / `DB_PORT` / `DB_DATABASE` / `DB_USERNAME` / `DB_PASSWORD` | managed MySQL credentials |

`SESSION_DRIVER`, `CACHE_STORE`, `QUEUE_CONNECTION`, and `FILESYSTEM_DISK`
already default to `database`/`database`/`database`/`local` in the config, so
they only need to be set if you change the defaults.

Notes:

- **Persistent files.** Without an attached volume, attachments and logs live
  in the container filesystem and are reset on redeploy/restart. Railway
  volumes (mount at `/var/www/html/storage`) fix this; on other platforms a
  demo can tolerate it.
- **Worker/scheduler are optional.** Notifications are sent synchronously
  today, so the web service alone is enough. Add a second service running
  `php artisan queue:work --sleep=3 --tries=3 --max-time=3600` (with
  `RUN_MIGRATIONS=0`) when jobs are introduced.
- **HTTPS URLs.** The app trusts the platform proxy (configured in
  `bootstrap/app.php`), so redirects and cookies use the correct scheme once
  `APP_URL` is set to the HTTPS domain.

---

## Railway (recommended for a quick demo)

Railway gives a `$5` one-time trial credit (30 days, no credit card), then a
Free plan with `$1/month` of credit, or Hobby at `$5/month` including `$5` of
usage. A small Laravel + MySQL app fits in the trial credit for roughly a
month of always-on hosting.

1. Push this repository to GitHub.
2. [railway.com](https://railway.com) → **New Project → Deploy from GitHub
   repo** and pick the repository. If it does not automatically choose the
   Dockerfile, set **Settings → Build → Builder: Dockerfile**.
3. In the project, **Create → Database → MySQL**.
4. On the **app service → Variables**, add the variables from the table above,
   plus these references to the MySQL service (replace `MySQL` with the actual
   service name):

   ```text
   DB_HOST=${{MySQL.MYSQLHOST}}
   DB_PORT=${{MySQL.MYSQLPORT}}
   DB_DATABASE=${{MySQL.MYSQLDATABASE}}
   DB_USERNAME=${{MySQL.MYSQLUSER}}
   DB_PASSWORD=${{MySQL.MYSQLPASSWORD}}
   ```

   (The `paas` start script also understands Railway's `MYSQLHOST`,
   `MYSQLPORT`, `MYSQLDATABASE`, `MYSQLUSER`, `MYSQLPASSWORD` variables
   directly.)

5. Deploy. The first boot waits for MySQL and runs the migrations
   (`RUN_MIGRATIONS=1`).
6. **Settings → Networking → Generate Domain**, copy the `https://…` URL into
   `APP_URL`, and redeploy.
7. Optional: **Variables → New Volume** mounted at `/var/www/html/storage` to
   keep attachments between deploys.
8. Optional demo accounts: Railway shell → `php artisan db:seed --force`
   (development seeder, password `password`; remove before sharing widely).

Health check path for the platform settings: `/up`.

## Koyeb (free, usually no credit card)

1. Create a Koyeb account with GitHub SSO.
2. **Create Service → GitHub**, pick the repository → **Builder: Dockerfile**,
   port `8080` (the image default; set `PORT` if Koyeb assigns another).
3. Set the environment variables from the table. For MySQL, Koyeb does not
   provide MySQL — use an external free instance (Aiven free MySQL or TiDB
   Cloud Starter). Both require TLS: download the provider CA file and point
   `MYSQL_ATTR_SSL_CA` at the system bundle (`/etc/ssl/certs/ca-certificates.crt`)
   when the provider uses a publicly trusted certificate, or bake the CA at
   `/usr/local/share/ca-certificates/`.
4. Deploy. The Koyeb Free Instance scales to zero after one hour without
   traffic and wakes in a few seconds on the next request.

## Northflank (free, always-on, card verification)

The Sandbox tier includes always-on compute, 2 services, 1 database addon
(MySQL available), and 2 cron jobs. A credit/debit card is required for card
verification (a hold, not a charge).

1. Create a project → **Create Service → Git repository** → Dockerfile,
   port `8080`.
2. Add the database addon (**MySQL**), then copy its connection details into
   the service's `DB_*` variables.
3. Set the remaining variables, deploy, and use the generated `*.code.run`
   HTTPS URL as `APP_URL`.
4. Persistent volumes are a paid feature; without one, attachments reset on
   redeploy.

## Render (free, sleeps after 15 minutes)

- **New → Web Service → Build from a Git repository → Docker** (Render uses
  the final `paas` stage), port `8080`, add the environment variables.
- Free web services spin down after 15 minutes without traffic and cold-start
  in about a minute. Free Render databases are PostgreSQL only, so use an
  external MySQL (Aiven/TiDB) as in the Koyeb section.
- The container filesystem is ephemeral: attachments and logs reset on
  redeploy and spin-down.

## After the first deploy

1. Open the HTTPS URL and log in (seed demo accounts if you ran the seeder).
2. Point the TestSprite project at the public URL (`testsprite project update
   <project-id> --url https://…`) so portal runs no longer need the local
   tunnel.
3. Keep `APP_DEBUG=false` and change/remove the demo passwords before sharing
   the link widely.
