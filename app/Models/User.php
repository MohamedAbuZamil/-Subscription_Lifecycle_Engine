<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens;

    protected $fillable = [
        'name',
        'email',
        'password',
        'is_admin',
        'balance',
        'auto_renewal',
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'password'     => 'hashed',
        'is_admin'     => 'boolean',
        'balance'      => 'decimal:2',
        'auto_renewal' => 'boolean',
    ];
}
