<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PlanPrice extends Model
{
    protected $fillable = [
        'plan_id',
        'billing_cycle',
        'currency',
        'price',
        'grace_period_days',
        'is_active',
        'external_price_id',
    ];

    protected $casts = [
        'price'              => 'decimal:2',
        'grace_period_days'  => 'integer',
        'is_active'          => 'boolean',
    ];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }
}
