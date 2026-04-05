<?php

namespace App\Http\Controllers;

use App\Models\Subscription;
use App\Models\SubscriptionTransaction;
use App\Http\Requests\SubscriptionTransaction\StoreSubscriptionTransactionRequest;
use App\Http\Requests\SubscriptionTransaction\UpdateSubscriptionTransactionRequest;
use App\Http\Requests\SubscriptionTransaction\MarkPaidTransactionRequest;
use App\Http\Requests\SubscriptionTransaction\MarkFailedTransactionRequest;
use App\Http\Requests\SubscriptionTransaction\RefundTransactionRequest;
use App\Http\Resources\SubscriptionTransactionResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Str;

class SubscriptionTransactionController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $user    = $request->user();
        $perPage = $user->is_admin
            ? min($request->integer('per_page', 15), 100)
            : min($request->integer('per_page', 10), 10);

        $query = SubscriptionTransaction::query();

        if ($user->is_admin) {
            $query->when($request->filled('subscription_id'), fn ($q) => $q->where('subscription_id', $request->integer('subscription_id')))
                  ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')));
        } else {
            $query->whereHas('subscription', fn ($q) => $q->where('user_id', $user->id));
        }

        return SubscriptionTransactionResource::collection($query->latest()->paginate($perPage));
    }

    public function store(StoreSubscriptionTransactionRequest $request): JsonResponse
    {
        if (! $request->user()->is_admin) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $subscription = Subscription::findOrFail($request->subscription_id);

        if (! in_array($subscription->status, ['pending', 'trialing', 'past_due'])) {
            return response()->json([
                'message' => 'Transactions can only be created for pending, trialing, or past_due subscriptions.',
            ], 422);
        }

        $planPrice = $subscription->planPrice;

        $transaction = SubscriptionTransaction::create([
            'subscription_id'         => $subscription->id,
            'reference'               => 'TXN-' . strtoupper(Str::random(12)),
            'amount'                  => $planPrice->price,
            'currency'                => $planPrice->currency,
            'status'                  => 'pending',
            'provider'                => $request->provider,
            'provider_transaction_id' => $request->provider_transaction_id,
            'metadata'                => $request->metadata,
        ]);

        return response()->json(['data' => new SubscriptionTransactionResource($transaction)], 201);
    }

    public function show(SubscriptionTransaction $transaction): JsonResponse
    {
        $user = request()->user();

        if (! $user->is_admin && $transaction->subscription->user_id !== $user->id) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        return response()->json(['data' => new SubscriptionTransactionResource($transaction)]);
    }

    public function update(UpdateSubscriptionTransactionRequest $request, SubscriptionTransaction $transaction): JsonResponse
    {
        if (! $request->user()->is_admin) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $transaction->update($request->validated());

        return response()->json(['data' => new SubscriptionTransactionResource($transaction)]);
    }

    public function markPaid(MarkPaidTransactionRequest $request, SubscriptionTransaction $transaction): JsonResponse
    {
        if (! $request->user()->is_admin) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        if ($transaction->status !== 'pending') {
            return response()->json(['message' => 'Only pending transactions can be marked as paid.'], 422);
        }

        $transaction->update([
            'status'  => 'paid',
            'paid_at' => now(),
        ]);

        $this->activateSubscription($transaction->subscription);

        return response()->json(['data' => new SubscriptionTransactionResource($transaction)]);
    }

    public function markFailed(MarkFailedTransactionRequest $request, SubscriptionTransaction $transaction): JsonResponse
    {
        if (! $request->user()->is_admin) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        if ($transaction->status !== 'pending') {
            return response()->json(['message' => 'Only pending transactions can be marked as failed.'], 422);
        }

        $transaction->update([
            'status'         => 'failed',
            'failed_at'      => $request->failed_at ?? now(),
            'failure_reason' => $request->failure_reason,
        ]);

        return response()->json(['data' => new SubscriptionTransactionResource($transaction)]);
    }

    public function refund(RefundTransactionRequest $request, SubscriptionTransaction $transaction): JsonResponse
    {
        if (! $request->user()->is_admin) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        if ($transaction->status !== 'paid') {
            return response()->json(['message' => 'Only paid transactions can be refunded.'], 422);
        }

        $transaction->update(['status' => 'refunded']);

        $user = $transaction->subscription->user;
        $user->increment('balance', $transaction->amount);

        return response()->json(['data' => new SubscriptionTransactionResource($transaction)]);
    }

    private function activateSubscription(Subscription $subscription): void
    {
        $now          = now();
        $billingCycle = $subscription->planPrice->billing_cycle;
        $periodEnd    = $billingCycle === 'yearly'
            ? $now->copy()->addYear()
            : $now->copy()->addMonth();

        if ($subscription->status === 'active') {
            $from      = $subscription->current_period_ends_at ?? $now;
            $periodEnd = $billingCycle === 'yearly'
                ? $from->copy()->addYear()
                : $from->copy()->addMonth();

            $subscription->update([
                'current_period_starts_at' => $from,
                'current_period_ends_at'   => $periodEnd,
                'grace_period_ends_at'     => null,
            ]);

            return;
        }

        $subscription->update([
            'status'                    => 'active',
            'current_period_starts_at'  => $now,
            'current_period_ends_at'    => $periodEnd,
            'grace_period_ends_at'      => null,
            'trial_ends_at'             => $subscription->status === 'trialing' ? $now : $subscription->trial_ends_at,
        ]);
    }
}
