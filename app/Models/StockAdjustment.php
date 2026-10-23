<?php

namespace App\Models;

class StockAdjustment extends TenantModel
{
    public bool $branchScoped = true;

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
