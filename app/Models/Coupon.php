<?php

namespace App\Models;

class Coupon extends TenantModel
{
    protected function casts(): array
    {
        return ['active' => 'boolean', 'expires_at' => 'date'];
    }
}
