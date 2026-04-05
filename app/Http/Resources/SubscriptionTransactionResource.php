<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubscriptionTransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                      => $this->id,
            'subscription_id'         => $this->subscription_id,
            'reference'               => $this->reference,
            'amount'                  => $this->amount,
            'currency'                => $this->currency,
            'status'                  => $this->status,
            'provider'                => $this->provider,
            'provider_transaction_id' => $this->provider_transaction_id,
            'paid_at'                 => $this->paid_at,
            'failed_at'               => $this->failed_at,
            'failure_reason'          => $this->failure_reason,
            'metadata'                => $this->metadata,
            'created_at'              => $this->created_at,
            'updated_at'              => $this->updated_at,
        ];
    }
}
