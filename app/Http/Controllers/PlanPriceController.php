<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\PlanPrice;
use App\Http\Requests\PlanPrice\StorePlanPriceRequest;
use App\Http\Requests\PlanPrice\UpdatePlanPriceRequest;
use App\Http\Requests\PlanPrice\PatchPlanPriceRequest;
use App\Http\Resources\PlanPriceResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PlanPriceController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $perPage = min($request->integer('per_page', 15), 100);

        $prices = PlanPrice::query()
            ->when($request->filled('plan_id'), fn ($q) => $q->where('plan_id', $request->integer('plan_id')))
            ->when($request->boolean('active_only'), fn ($q) => $q->where('is_active', true))
            ->paginate($perPage);

        return PlanPriceResource::collection($prices);
    }

    public function store(StorePlanPriceRequest $request): JsonResponse
    {
        $plan = Plan::find($request->plan_id);

        if (! $plan) {
            return response()->json(['message' => 'Plan not found.'], 404);
        }

        if (! $plan->is_active) {
            return response()->json(['message' => 'Cannot add price to an inactive plan.'], 422);
        }

        $exists = PlanPrice::where('plan_id', $request->plan_id)
            ->where('billing_cycle', $request->billing_cycle)
            ->where('currency', $request->currency)
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'A price for this plan, billing cycle, and currency already exists.',
            ], 409);
        }

        $price = PlanPrice::create($request->validated());

        return response()->json(['data' => new PlanPriceResource($price)], 201);
    }

    public function show(PlanPrice $planPrice): JsonResponse
    {
        return response()->json(['data' => new PlanPriceResource($planPrice)]);
    }

    public function update(UpdatePlanPriceRequest $request, PlanPrice $planPrice): JsonResponse
    {
        $planPrice->update($request->validated());

        return response()->json(['data' => new PlanPriceResource($planPrice)]);
    }

    public function patch(PatchPlanPriceRequest $request, PlanPrice $planPrice): JsonResponse
    {
        $planPrice->update($request->validated());

        return response()->json(['data' => new PlanPriceResource($planPrice)]);
    }

    public function destroy(PlanPrice $planPrice): JsonResponse
    {
        $hasSubscriptions = $planPrice->subscriptions()->exists();

        if ($hasSubscriptions) {
            return response()->json([
                'message' => 'Cannot delete price. It has associated subscriptions.',
            ], 409);
        }

        $planPrice->delete();

        return response()->json(null, 204);
    }
}
