<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\PlanPrice;
use App\Models\Subscription;
use App\Http\Requests\Subscription\StoreSubscriptionRequest;
use App\Http\Requests\Subscription\UpdateSubscriptionRequest;
use App\Http\Requests\Subscription\CancelSubscriptionRequest;
use App\Http\Requests\Subscription\RenewSubscriptionRequest;
use App\Http\Requests\Subscription\MarkPastDueSubscriptionRequest;
use App\Http\Requests\Subscription\ActivateSubscriptionRequest;
use App\Http\Requests\Subscription\ExpireSubscriptionRequest;
use App\Http\Resources\SubscriptionResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SubscriptionController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $user    = $request->user();
        $perPage = $user->is_admin
            ? min($request->integer('per_page', 15), 100)
            : min($request->integer('per_page', 10), 10);

        $query = Subscription::query();

        if ($user->is_admin) {
            $query->when($request->filled('user_id'), fn ($q) => $q->where('user_id', $request->integer('user_id')))
                  ->when($request->filled('status'),  fn ($q) => $q->where('status', $request->string('status')));
        } else {
            $query->where('user_id', $user->id);
        }

        return SubscriptionResource::collection($query->latest()->paginate($perPage));
    }

    public function store(StoreSubscriptionRequest $request): JsonResponse
    {
        $user  = $request->user();
        $plan  = Plan::findOrFail($request->plan_id);

        if (! $plan->is_active) {
            return response()->json(['message' => 'Plan is not active.'], 422);
        }

        $currency  = $this->detectCurrency($request);
        $planPrice = $this->resolvePlanPrice($plan->id, $request->billing_cycle, $currency);

        if (! $planPrice) {
            return response()->json([
                'message' => 'No active price found for this plan and billing cycle.',
            ], 422);
        }

        $hasActive = Subscription::where('user_id', $user->id)
            ->active()
            ->exists();

        if ($hasActive) {
            return response()->json(['message' => 'You already have an active subscription.'], 409);
        }

        $hadTrial = Subscription::where('user_id', $user->id)
            ->whereNotNull('trial_starts_at')
            ->exists();

        $giveTrial = ! $hadTrial && $plan->trial_days > 0;
        $now       = now();

        $data = [
            'user_id'       => $user->id,
            'plan_id'       => $plan->id,
            'plan_price_id' => $planPrice->id,
            'status'        => $giveTrial ? 'trialing' : 'pending',
            'started_at'    => $now,
        ];

        if ($giveTrial) {
            $data['trial_starts_at'] = $now;
            $data['trial_ends_at']   = $now->copy()->addDays($plan->trial_days);
        }

        $subscription = Subscription::create($data);

        return response()->json(['data' => new SubscriptionResource($subscription)], 201);
    }

    public function show(Subscription $subscription): JsonResponse
    {
        $user = request()->user();

        if (! $user->is_admin && $subscription->user_id !== $user->id) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        return response()->json(['data' => new SubscriptionResource($subscription)]);
    }

    public function update(UpdateSubscriptionRequest $request, Subscription $subscription): JsonResponse
    {
        if (! $request->user()->is_admin) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $subscription->update([
            'current_period_ends_at' => $request->current_period_ends_at,
        ]);

        return response()->json(['data' => new SubscriptionResource($subscription)]);
    }

    public function activate(ActivateSubscriptionRequest $request, Subscription $subscription): JsonResponse
    {
        if (! in_array($subscription->status, ['pending', 'trialing', 'past_due'])) {
            return response()->json([
                'message' => 'Only pending, trialing, or past_due subscriptions can be activated.',
            ], 422);
        }

        $now          = now();
        $billingCycle = $subscription->planPrice->billing_cycle;
        $periodEnd    = $billingCycle === 'yearly'
            ? $now->copy()->addYear()
            : $now->copy()->addMonth();

        $subscription->update([
            'status'                    => 'active',
            'current_period_starts_at'  => $now,
            'current_period_ends_at'    => $periodEnd,
            'grace_period_ends_at'      => null,
            'trial_ends_at'             => $subscription->status === 'trialing' ? $now : $subscription->trial_ends_at,
        ]);

        return response()->json(['data' => new SubscriptionResource($subscription)]);
    }

    public function cancel(CancelSubscriptionRequest $request, Subscription $subscription): JsonResponse
    {
        $user = $request->user();

        if ($subscription->user_id !== $user->id) {
            return response()->json(['message' => 'Forbidden. Only the subscription owner can cancel.'], 403);
        }

        if (! in_array($subscription->status, ['active', 'trialing', 'past_due'])) {
            return response()->json([
                'message' => 'Only active, trialing, or past_due subscriptions can be canceled.',
            ], 422);
        }

        $subscription->update([
            'status'      => 'canceled',
            'canceled_at' => now(),
        ]);

        return response()->json(['data' => new SubscriptionResource($subscription)]);
    }

    public function renew(RenewSubscriptionRequest $request, Subscription $subscription): JsonResponse
    {
        if (! $request->user()->is_admin) {
            return response()->json(['message' => 'Forbidden. Renewals are managed by the system.'], 403);
        }

        if ($subscription->status !== 'active') {
            return response()->json(['message' => 'Only active subscriptions can be renewed.'], 422);
        }

        $billingCycle = $subscription->planPrice->billing_cycle;
        $from         = $subscription->current_period_ends_at ?? now();
        $periodEnd    = $billingCycle === 'yearly'
            ? $from->copy()->addYear()
            : $from->copy()->addMonth();

        $subscription->update([
            'current_period_starts_at' => $from,
            'current_period_ends_at'   => $periodEnd,
            'grace_period_ends_at'     => null,
        ]);

        return response()->json(['data' => new SubscriptionResource($subscription)]);
    }

    public function markPastDue(MarkPastDueSubscriptionRequest $request, Subscription $subscription): JsonResponse
    {
        if ($subscription->status !== 'active') {
            return response()->json(['message' => 'Only active subscriptions can be marked past due.'], 422);
        }

        $graceDays = $subscription->planPrice->grace_period_days ?? 0;

        $subscription->update([
            'status'              => 'past_due',
            'grace_period_ends_at' => $graceDays > 0 ? now()->addDays($graceDays) : null,
        ]);

        return response()->json(['data' => new SubscriptionResource($subscription)]);
    }

    public function expire(ExpireSubscriptionRequest $request, Subscription $subscription): JsonResponse
    {
        if (! in_array($subscription->status, ['past_due', 'canceled'])) {
            return response()->json([
                'message' => 'Only past_due or canceled subscriptions can be expired.',
            ], 422);
        }

        $subscription->update([
            'status'     => 'expired',
            'expires_at' => now(),
        ]);

        return response()->json(['data' => new SubscriptionResource($subscription)]);
    }

    private function detectCurrency(Request $request): string
    {
        if (app()->environment('local', 'testing')) {
            $override = $request->header('X-Country');
            if ($override === 'AE') {
                return 'AED';
            }
        }

        $ip = $request->ip();

        $uaePrefixes = [
            '5.62.60.', '5.62.61.', '5.62.62.', '5.62.63.',
            '80.249.',
            '94.200.', '94.201.', '94.202.', '94.203.',
            '213.42.', '213.45.',
            '185.87.',
            '109.200.',
        ];

        foreach ($uaePrefixes as $prefix) {
            if (str_starts_with($ip, $prefix)) {
                return 'AED';
            }
        }

        return 'USD';
    }

    private function resolvePlanPrice(int $planId, string $billingCycle, string $currency): ?PlanPrice
    {
        $price = PlanPrice::where('plan_id', $planId)
            ->where('billing_cycle', $billingCycle)
            ->where('currency', $currency)
            ->where('is_active', true)
            ->first();

        if (! $price && $currency === 'AED') {
            $price = PlanPrice::where('plan_id', $planId)
                ->where('billing_cycle', $billingCycle)
                ->where('currency', 'USD')
                ->where('is_active', true)
                ->first();
        }

        return $price;
    }
}
