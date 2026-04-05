<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Http\Requests\Plan\StorePlanRequest;
use App\Http\Requests\Plan\UpdatePlanRequest;
use App\Http\Requests\Plan\PatchPlanRequest;
use App\Http\Resources\PlanResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PlanController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $user    = $request->user();
        $perPage = min($request->integer('per_page', 15), 100);

        $plans = Plan::query()
            ->when(! $user->is_admin, fn ($q) => $q->where('is_active', true))
            ->when($user->is_admin && $request->boolean('active_only'), fn ($q) => $q->where('is_active', true))
            ->paginate($perPage);

        return PlanResource::collection($plans);
    }

    public function publicIndex(Request $request): AnonymousResourceCollection
    {
        $perPage = min($request->integer('per_page', 10), 10);

        $plans = Plan::query()
            ->where('is_active', true)
            ->paginate($perPage);

        return PlanResource::collection($plans);
    }

    public function store(StorePlanRequest $request): JsonResponse
    {
        $plan = Plan::create($request->validated());

        return response()->json(['data' => new PlanResource($plan)], 201);
    }

    public function show(Plan $plan): JsonResponse
    {
        return response()->json(['data' => new PlanResource($plan)]);
    }

    public function userShow(Plan $plan): JsonResponse
    {
        if (! $plan->is_active) {
            return response()->json(['message' => 'Plan not found.'], 404);
        }

        $plan->load(['prices' => fn ($q) => $q->where('is_active', true)]);

        return response()->json(['data' => new PlanResource($plan)]);
    }

    public function update(UpdatePlanRequest $request, Plan $plan): JsonResponse
    {
        $plan->update($request->validated());

        return response()->json(['data' => new PlanResource($plan)]);
    }

    public function patch(PatchPlanRequest $request, Plan $plan): JsonResponse
    {
        $plan->update($request->validated());

        return response()->json(['data' => new PlanResource($plan)]);
    }

    public function destroy(Plan $plan): JsonResponse
    {
        if ($plan->prices()->exists()) {
            return response()->json([
                'message' => 'Cannot delete plan. It has associated prices.',
            ], 409);
        }

        if ($plan->subscriptions()->exists()) {
            return response()->json([
                'message' => 'Cannot delete plan. It has active subscriptions.',
            ], 409);
        }

        $plan->delete();

        return response()->json(null, 204);
    }
}
