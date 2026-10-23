<?php

namespace App\Models;

class Stock extends TenantModel
{
    public bool $branchScoped = true;

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
