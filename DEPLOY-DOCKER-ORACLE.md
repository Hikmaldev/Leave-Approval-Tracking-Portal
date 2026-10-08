# Deploy with Docker on Oracle Cloud Always Free

This repository ships with a production Docker setup:

| File | Purpose |
|---|---|
| `Dockerfile` | Multi-stage build: `assets` builds the Vite bundle, `web` is a self-contained Nginx image serving `public/`, `app` is the PHP 8.3 FPM image running Laravel. |
| `docker-compose.yml` | Runs `app`, `web`, `worker`, `scheduler`, and `db` (MySQL 8.0) with persistent volumes. |
| `docker/entrypoint.sh` | Prepares storage permissions, optionally waits for MySQL and runs `php artisan migrate --force` (`RUN_MIGRATIONS=1`). |
| `docker/nginx/default.conf` | Nginx site: static files plus `fastcgi_pass app:9000`. |
| `docker/php/extra.ini` | PHP/OPcache production settings. |

All images build for `linux/amd64` and `linux/arm64`, so the same setup runs on
the Oracle Ampere A1 VM and on a local Windows machine with Docker Desktop.

Persistent Docker volumes:

- `app-storage` → `/var/www/html/storage` (private attachments, logs, framework cache).
- `db-data` → `/var/lib/mysql` (database).

## 1. Create the Oracle VM

1. OCI Console → **Compute → Instances → Create instance**.
2. Image: **Ubuntu 24.04** (aarch64) · Shape: **VM.Standard.A1.Flex**, 2 OCPU / 12 GB.
   - If you see *"Out of host capacity"*, try another availability domain/region or retry later (retry scripts like `oci-arm-catcher` work well).
3. Add your SSH public key and create the instance.
4. Open port 80 (and 443 if you add HTTPS later) in **two places**:
   - **VCN → Security Lists → Default Security List → Add Ingress Rule**: TCP 80 from `0.0.0.0/0`.
   - **Inside the VM** (Oracle Ubuntu images block traffic with iptables):

     ```bash
     sudo iptables -I INPUT 6 -m state --state NEW -p tcp --dport 80 -j ACCEPT
     sudo iptables -I INPUT 6 -m state --state NEW -p tcp --dport 443 -j ACCEPT
     sudo netfilter-persistent save
     ```

     If the command is missing: `sudo apt-get install -y iptables-persistent`.

## 2. Install Docker

```bash
sudo apt-get update && sudo apt-get install -y git
curl -fsSL https://get.docker.com | sudo sh
sudo usermod -aG docker "$USER"
# Log out and back in so the group change applies.
```

## 3. Get the code and configure `.env`

```bash
git clone <your-repo-url> leave-portal
cd leave-portal
cp .env.example .env
```

Edit `.env` with the production values:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=http://<VM_PUBLIC_IP>
LOG_CHANNEL=stderr
LOG_LEVEL=warning

DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=leave_portal
DB_USERNAME=leave_portal
DB_PASSWORD=<strong-password>

# Docker-only: root password for the MySQL container itself.
MYSQL_ROOT_PASSWORD=<another-strong-password>
```

Notes:

- `DB_USERNAME` **must not be `root`**; the MySQL image refuses to create a root application user.
- `docker-compose.yml` reads `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`, and `MYSQL_ROOT_PASSWORD` from this same `.env` file and overrides `DB_HOST=db` for the app containers.
- Optional: set `WEB_PORT=8080` if host port 80 is already used.

Generate the application key. Either locally (Laragon has PHP 8.3):

```bash
php artisan key:generate --show
```

or after building the image:

```bash
docker compose build app
docker compose run --rm --no-deps --entrypoint php app artisan key:generate --show
```

Copy the printed `base64:...` value into `APP_KEY=` in `.env`.

## 4. Build and start

```bash
docker compose config --quiet   # validate the compose file
docker compose build
docker compose up -d
```

On first start the `app` entrypoint waits for MySQL and runs the migrations.
`web`, `worker`, and `scheduler` wait until `app` is healthy.

## 5. Verify

```bash
docker compose ps
docker compose exec app php artisan migrate:status
curl -I http://localhost/up        # Laravel health endpoint -> 200
```

Open `http://<VM_PUBLIC_IP>` in a browser.

Optional development accounts for a demo (development seeder, password
`password` — do not keep these on a public production instance):

```bash
docker compose exec app php artisan db:seed --force
```

## Updating the application

```bash
git pull
docker compose up -d --build
```

`app-storage` and `db-data` survive rebuilds. Code changes always require a
rebuild because OPcache has `validate_timestamps=0`.

## Operations

```bash
docker compose logs -f app          # application logs (LOG_CHANNEL=stderr)
docker compose logs -f web db
docker compose exec app php artisan tinker
docker compose exec app php artisan optimize          # cache config/routes/views
docker compose exec app php artisan optimize:clear    # after .env changes
```

Backups:

```bash
# Database
docker compose exec -T db sh -c 'exec mysqldump -uroot -p"$MYSQL_ROOT_PASSWORD" "$MYSQL_DATABASE"' > leave_portal-$(date +%F).sql

# Attachments and storage (check the volume name with: docker volume ls)
docker run --rm -v leave-portal_app-storage:/data -v "$PWD":/backup alpine \
    tar czf /backup/app-storage-$(date +%F).tgz -C /data .
```

## HTTPS

The application needs a public HTTPS URL for shared demos and for TestSprite
portal runs. Two free options:

1. **Cloudflare Tunnel** (no open ports, no domain required for a quick tunnel):

   ```bash
   docker run -d --name cloudflared --restart unless-stopped \
       --network leave-portal_default \
       cloudflare/cloudflared:latest tunnel --no-autoupdate --url http://web:80
   docker logs cloudflared    # read the https://*.trycloudflare.com URL
   ```

   Quick-tunnel URLs are ephemeral. For a stable hostname, use a named tunnel
   with a domain on Cloudflare.

2. **Caddy with a free `sslip.io` hostname** (ports 80/443 required, and the
   `web` port mapping should be removed so Caddy owns port 80):

   ```bash
   docker run -d --name caddy --restart unless-stopped \
       --network leave-portal_default \
       -p 443:443 \
       caddy:2-alpine \
       caddy reverse-proxy --from https://<VM_PUBLIC_IP>.sslip.io --to web:80
   ```

   Then set `APP_URL=https://<VM_PUBLIC_IP>.sslip.io`. Forwarded headers are
   already trusted in `bootstrap/app.php`, so the app detects HTTPS correctly;
   optionally set `SESSION_SECURE_COOKIE=true`.

## Troubleshooting

| Symptom | Cause / fix |
|---|---|
| `502 Bad Gateway` on every page | `app` is not healthy yet (migrations still running) or crashed — check `docker compose logs app`. |
| `db` exits with "MYSQL_USER=root" | Set `DB_USERNAME` to a non-root user in `.env` (e.g. `leave_portal`). |
| Compose fails with `${DB_PASSWORD must be set...}` | Fill `DB_PASSWORD` and `MYSQL_ROOT_PASSWORD` in `.env`. |
| Site unreachable from the internet | Port 80 is not open in the OCI Security List **and** in the VM iptables (see step 1). |
| Code changes not visible after `git pull` | Rebuild: `docker compose up -d --build` (OPcache will not revalidate old files). |
| Login redirects to `http://` behind HTTPS | Configure trusted proxies and `APP_URL` as described in the HTTPS section. |
| Attachments lost after redeploy | They live in the `app-storage` volume — do not remove volumes (`docker compose down -v` deletes them). |
