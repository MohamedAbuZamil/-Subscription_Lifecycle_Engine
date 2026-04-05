<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use Illuminate\Console\Command;

class ExpireOverdueSubscriptions extends Command
{
    protected $signature   = 'subscriptions:expire-overdue';
    protected $description = 'Expire past_due subscriptions whose grace period has ended';

    public function handle(): void
    {
        $count = Subscription::where('status', 'past_due')
            ->whereNotNull('grace_period_ends_at')
            ->where('grace_period_ends_at', '<', now())
            ->update([
                'status'      => 'canceled',
                'canceled_at' => now(),
            ]);

        $this->info("Canceled {$count} overdue subscription(s) after grace period.");
    }
}
