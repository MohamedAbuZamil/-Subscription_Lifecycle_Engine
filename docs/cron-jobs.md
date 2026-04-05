# Cron Jobs — Subscription Lifecycle Engine

## How It Works

Laravel uses a **single cron entry** that runs every minute.
Inside, Laravel checks its schedule and runs only the commands that are due.

The schedule is defined in:
```
routes/console.php
```

---

## Registered Scheduled Commands

Commands run in this order every night to ensure correct state transitions:

| Time  | Command | Description |
|---|---|---|
| 00:00 | `subscriptions:expire-trials` | Moves `trialing` subs whose `trial_ends_at` has passed → `pending` |
| 00:05 | `subscriptions:auto-renew` | Deducts balance and renews `active` subs whose period ended (if `auto_renewal = true`). If balance insufficient → `past_due` |
| 00:10 | `subscriptions:expire-overdue` | Cancels `past_due` subs whose `grace_period_ends_at` has passed → `canceled` |

### Nightly Flow

```
00:00 — expire-trials:
  trialing + trial_ends_at < now → pending

00:05 — auto-renew:
  active + period_ends < now + auto_renewal=true:
    balance >= price → deduct + renew period + create paid transaction
    balance < price  → past_due + grace_period_ends_at

00:10 — expire-overdue:
  past_due + grace_period_ends_at < now → canceled
```

> **All commands use bulk DB operations** — efficient regardless of user count.

---

## Setup

### Linux / Ubuntu (Production)

Add this single entry to the server crontab via `crontab -e`:

```cron
* * * * * cd /var/www/your-project && php artisan schedule:run >> /dev/null 2>&1
```

Replace `/var/www/your-project` with the actual project path.

---

### Windows (Development)

No cron needed. Run manually when needed:

```bash
php artisan subscriptions:expire-overdue
```

Or test the scheduler directly:

```bash
php artisan schedule:run
```

---

## Testing the Command

```bash
# Run and see output
php artisan subscriptions:expire-overdue

# Expected output:
# Canceled 0 overdue subscription(s) after grace period.   ← if none are overdue
# Canceled 3 overdue subscription(s) after grace period.   ← if 3 were overdue
```
