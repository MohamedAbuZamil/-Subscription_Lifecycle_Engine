<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubscriptionTransaction extends Model
{
    protected $fillable = [
        'subscription_id',
        'reference',
        'amount',
        'currency',
        'status',
        'provider',
        'provider_transaction_id',
        'paid_at',
        'failed_at',
        'failure_reason',
        'metadata',
    ];

    protected $casts = [
        'amount'    => 'decimal:2',
        'paid_at'   => 'datetime',
        'failed_at' => 'datetime',
        'metadata'  => 'array',
    ];

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }
}
