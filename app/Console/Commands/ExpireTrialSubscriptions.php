<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use Illuminate\Console\Command;

class ExpireTrialSubscriptions extends Command
{
    protected $signature   = 'subscriptions:expire-trials';
    protected $description = 'Move trialing subscriptions whose trial period has ended to pending (awaiting payment)';

    public function handle(): void
    {
        $count = Subscription::where('status', 'trialing')
            ->whereNotNull('trial_ends_at')
            ->where('trial_ends_at', '<', now())
            ->update(['status' => 'pending']);

        $this->info("Moved {$count} subscription(s) from trialing to pending.");
    }
}
