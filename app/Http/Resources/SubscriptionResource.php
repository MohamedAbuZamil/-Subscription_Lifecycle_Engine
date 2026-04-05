<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubscriptionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                        => $this->id,
            'user_id'                   => $this->user_id,
            'plan_id'                   => $this->plan_id,
            'plan_price_id'             => $this->plan_price_id,
            'status'                    => $this->status,
            'started_at'                => $this->started_at,
            'trial_starts_at'           => $this->trial_starts_at,
            'trial_ends_at'             => $this->trial_ends_at,
            'current_period_starts_at'  => $this->current_period_starts_at,
            'current_period_ends_at'    => $this->current_period_ends_at,
            'grace_period_ends_at'      => $this->grace_period_ends_at,
            'canceled_at'               => $this->canceled_at,
            'expires_at'                => $this->expires_at,
            'created_at'                => $this->created_at,
            'updated_at'                => $this->updated_at,
        ];
    }
}
