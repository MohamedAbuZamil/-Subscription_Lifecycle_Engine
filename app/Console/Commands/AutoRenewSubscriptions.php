<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use App\Models\SubscriptionTransaction;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class AutoRenewSubscriptions extends Command
{
    protected $signature   = 'subscriptions:auto-renew';
    protected $description = 'Auto-renew active subscriptions whose period has ended and user has auto_renewal enabled';

    public function handle(): void
    {
        $renewed = 0;
        $failed  = 0;

        $subscriptions = Subscription::with(['user', 'planPrice'])
            ->where('status', 'active')
            ->whereNotNull('current_period_ends_at')
            ->where('current_period_ends_at', '<', now())
            ->whereHas('user', fn ($q) => $q->where('auto_renewal', true))
            ->get();

        foreach ($subscriptions as $subscription) {
            $user      = $subscription->user;
            $planPrice = $subscription->planPrice;

            if ($user->balance >= $planPrice->price) {
                $user->decrement('balance', $planPrice->price);

                $billingCycle = $planPrice->billing_cycle;
                $from         = $subscription->current_period_ends_at;
                $periodEnd    = $billingCycle === 'yearly'
                    ? $from->copy()->addYear()
                    : $from->copy()->addMonth();

                $subscription->update([
                    'current_period_starts_at' => $from,
                    'current_period_ends_at'   => $periodEnd,
                    'grace_period_ends_at'     => null,
                ]);

                SubscriptionTransaction::create([
                    'subscription_id' => $subscription->id,
                    'reference'       => 'AUTO-' . strtoupper(Str::random(12)),
                    'amount'          => $planPrice->price,
                    'currency'        => $planPrice->currency,
                    'status'          => 'paid',
                    'paid_at'         => now(),
                    'provider'        => 'balance',
                    'metadata'        => ['auto_renewal' => true],
                ]);

                $renewed++;
            } else {
                $graceDays = $planPrice->grace_period_days ?? 0;

                $subscription->update([
                    'status'               => 'past_due',
                    'grace_period_ends_at' => $graceDays > 0 ? now()->addDays($graceDays) : null,
                ]);

                $failed++;
            }
        }

        $this->info("Auto-renewed: {$renewed} | Insufficient balance (past_due): {$failed}");
    }
}
