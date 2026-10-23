<?php

namespace App\Models;

class GiftCard extends TenantModel
{
    public bool $branchScoped = false;

    protected function casts(): array
    {
        return ['expires_at' => 'date'];
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
