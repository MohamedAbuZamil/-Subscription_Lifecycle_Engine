# Deployment Guide

How to run the Subscription Lifecycle Engine on a local machine or production server.

---

## Local Development (Docker + PHP)

### Step 1 — Clone and configure

```bash
git clone https://github.com/your-username/subscription-lifecycle-engine.git
cd subscription-lifecycle-engine

cp .env.example .env
```

Edit `.env` — set your DB credentials (defaults work with Docker below).

### Step 2 — Install dependencies

```bash
composer install
php artisan key:generate
```

### Step 3 — Start the database (Docker)

```bash
docker-compose up -d
```

This starts MySQL on port `3306` with:
- Database: `subscription_engine`
- User: `app_user` / Password: `app_password`

Wait ~5 seconds for MySQL to be ready.

### Step 4 — Run migrations and seed

```bash
php artisan migrate --seed
```

This creates all tables and seeds:
- Admin user: `admin@admin.com` / `admin123`

### Step 5 — Start the server

```bash
php artisan serve
```

Server runs at: `http://127.0.0.1:8000`

---

## Production Server (Ubuntu / Nginx)

### Step 1 — Install dependencies

```bash
sudo apt update
sudo apt install php8.2 php8.2-fpm php8.2-mysql php8.2-mbstring php8.2-xml php8.2-curl nginx mysql-server composer -y
```

### Step 2 — Clone and configure

```bash
cd /var/www
git clone https://github.com/your-username/subscription-lifecycle-engine.git
cd subscription-lifecycle-engine

cp .env.example .env
```

Edit `.env`:
```
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

DB_HOST=127.0.0.1
DB_DATABASE=subscription_engine
DB_USERNAME=your_db_user
DB_PASSWORD=your_db_password
```

### Step 3 — Install and setup

```bash
composer install --no-dev --optimize-autoloader
php artisan key:generate
php artisan migrate --force
php artisan config:cache
php artisan route:cache
```

### Step 4 — Nginx config

```nginx
server {
    listen 80;
    server_name yourdomain.com;
    root /var/www/subscription-lifecycle-engine/public;

    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

```bash
sudo nginx -t && sudo systemctl reload nginx
```

### Step 5 — Set permissions

```bash
sudo chown -R www-data:www-data /var/www/subscription-lifecycle-engine
sudo chmod -R 775 storage bootstrap/cache
```

### Step 6 — Setup Cron

```bash
crontab -e
```

Add:
```
* * * * * cd /var/www/subscription-lifecycle-engine && php artisan schedule:run >> /dev/null 2>&1
```

---

## Verify Everything Works

```bash
# Check server responds
curl http://127.0.0.1:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@admin.com","password":"admin123"}'

# Should return: { "token": "...", "data": { ... } }
```

---

## Scheduled Commands Reference

| Command | Runs At | Action |
|---|---|---|
| `subscriptions:expire-trials` | 00:00 | `trialing` → `pending` when trial_ends_at passed |
| `subscriptions:auto-renew` | 00:05 | Deducts balance and renews active subscriptions |
| `subscriptions:expire-overdue` | 00:10 | `past_due` → `canceled` when grace_period_ends_at passed |

Run manually:
```bash
php artisan subscriptions:expire-trials
php artisan subscriptions:auto-renew
php artisan subscriptions:expire-overdue
```
