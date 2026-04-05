<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('subscriptions:expire-trials')->dailyAt('00:00');
Schedule::command('subscriptions:auto-renew')->dailyAt('00:05');
Schedule::command('subscriptions:expire-overdue')->dailyAt('00:10');
