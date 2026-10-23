<?php

namespace App\Models;

class LoyaltyEntry extends TenantModel
{
    public bool $branchScoped = true;

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }
}
