<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlanPriceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                 => $this->id,
            'plan_id'            => $this->plan_id,
            'billing_cycle'      => $this->billing_cycle,
            'currency'           => $this->currency,
            'price'              => $this->price,
            'grace_period_days'  => $this->grace_period_days,
            'is_active'          => $this->is_active,
            'external_price_id'  => $this->external_price_id,
            'created_at'         => $this->created_at,
            'updated_at'         => $this->updated_at,
        ];
    }
}
