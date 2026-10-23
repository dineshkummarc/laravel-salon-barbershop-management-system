<?php

namespace App\Models;

class Purchase extends TenantModel
{
    public bool $branchScoped = true;

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
