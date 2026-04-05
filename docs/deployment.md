# Deployment Guide

How to run the Subscription Lifecycle Engine on a local machine or production server.

> **Note:** The database schema uses raw SQL migration files located in `database/migrations/`. Run them manually as shown below — do not use `php artisan migrate`.

---

## Local Development (Docker + PHP)

### Step 1 — Clone and configure

```bash
git clone https://github.com/MohamedAbuZamil/-Subscription_Lifecycle_Engine.git
cd ~/-Subscription_Lifecycle_Engine

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

### Step 4 — Run SQL migrations and seed

```bash
mysql -u app_user -p'app_password' subscription_engine < database/migrations/users_2026_05_4.sql
mysql -u app_user -p'app_password' subscription_engine < database/migrations/personal_access_tokens_2026_05_4.sql
mysql -u app_user -p'app_password' subscription_engine < database/migrations/plans_2026_05_4.sql
mysql -u app_user -p'app_password' subscription_engine < database/migrations/plan_prices_2026_05_4.sql
mysql -u app_user -p'app_password' subscription_engine < database/migrations/subscriptions_2026_05_4.sql
mysql -u app_user -p'app_password' subscription_engine < database/migrations/subscription_transactions_2026_05_4.sql
mysql -u app_user -p'app_password' subscription_engine < database/migrations/add_is_admin_to_users_2026_05_4.sql
mysql -u app_user -p'app_password' subscription_engine < database/migrations/add_balance_to_users_2026_05_4.sql
mysql -u app_user -p'app_password' subscription_engine < database/seeders/create_default_admin_2026_05_4.sql
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

### Step 1 — Install PHP and dependencies

```bash
sudo apt update
sudo apt install -y php8.4 php8.4-fpm php8.4-mysql php8.4-mbstring php8.4-xml php8.4-curl php8.4-zip php8.4-bcmath nginx mysql-server
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

### Step 2 — Setup MySQL

```bash
sudo systemctl start mysql
sudo mysql -e "CREATE DATABASE subscription_engine; CREATE USER 'app_user'@'localhost' IDENTIFIED BY 'app_password'; GRANT ALL PRIVILEGES ON subscription_engine.* TO 'app_user'@'localhost'; FLUSH PRIVILEGES;"
```

### Step 3 — Clone and configure

```bash
cd /var/www
git clone https://github.com/MohamedAbuZamil/-Subscription_Lifecycle_Engine.git
cd /var/www/-Subscription_Lifecycle_Engine

cp .env.example .env
```

Edit `.env`:
```
APP_ENV=production
APP_DEBUG=false
APP_URL=http://92.222.226.72

DB_HOST=127.0.0.1
DB_DATABASE=subscription_engine
DB_USERNAME=app_user
DB_PASSWORD=app_password
```

### Step 4 — Install and run migrations

```bash
composer install --no-dev --optimize-autoloader
php artisan key:generate

mysql -u app_user -p'app_password' subscription_engine < database/migrations/users_2026_05_4.sql
mysql -u app_user -p'app_password' subscription_engine < database/migrations/personal_access_tokens_2026_05_4.sql
mysql -u app_user -p'app_password' subscription_engine < database/migrations/plans_2026_05_4.sql
mysql -u app_user -p'app_password' subscription_engine < database/migrations/plan_prices_2026_05_4.sql
mysql -u app_user -p'app_password' subscription_engine < database/migrations/subscriptions_2026_05_4.sql
mysql -u app_user -p'app_password' subscription_engine < database/migrations/subscription_transactions_2026_05_4.sql
mysql -u app_user -p'app_password' subscription_engine < database/migrations/add_is_admin_to_users_2026_05_4.sql
mysql -u app_user -p'app_password' subscription_engine < database/migrations/add_balance_to_users_2026_05_4.sql
mysql -u app_user -p'app_password' subscription_engine < database/seeders/create_default_admin_2026_05_4.sql

php artisan config:cache
php artisan route:cache
```

### Step 5 — Quick serve (for testing)

```bash
php artisan serve --host=0.0.0.0 --port=8000
```

API accessible at: `http://92.222.226.72:8000/api`

### Step 6 — Nginx config (for production)

```nginx
server {
    listen 80;
    server_name 92.222.226.72;
    root /var/www/-Subscription_Lifecycle_Engine/public;

    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.4-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

```bash
sudo nginx -t
sudo systemctl reload nginx
```

### Step 7 — Set permissions

```bash
sudo chown -R www-data:www-data /var/www/-Subscription_Lifecycle_Engine
sudo chmod -R 775 storage bootstrap/cache
```

### Step 8 — Setup Cron

```bash
crontab -e
```

Add:
```
* * * * * cd /var/www/-Subscription_Lifecycle_Engine && php artisan schedule:run >> /dev/null 2>&1
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
