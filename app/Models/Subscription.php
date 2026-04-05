<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class Subscription extends Model
{
    protected $fillable = [
        'user_id',
        'plan_id',
        'plan_price_id',
        'status',
        'started_at',
        'trial_starts_at',
        'trial_ends_at',
        'current_period_starts_at',
        'current_period_ends_at',
        'grace_period_ends_at',
        'canceled_at',
        'expires_at',
    ];

    protected $casts = [
        'started_at'                => 'datetime',
        'trial_starts_at'           => 'datetime',
        'trial_ends_at'             => 'datetime',
        'current_period_starts_at'  => 'datetime',
        'current_period_ends_at'    => 'datetime',
        'grace_period_ends_at'      => 'datetime',
        'canceled_at'               => 'datetime',
        'expires_at'                => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', ['active', 'trialing', 'past_due']);
    }

    public function planPrice(): BelongsTo
    {
        return $this->belongsTo(PlanPrice::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(SubscriptionTransaction::class);
    }
}
