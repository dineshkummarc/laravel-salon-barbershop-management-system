<?php

namespace App\Models;

class Transfer extends TenantModel
{
    public bool $branchScoped = true;

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function destination()
    {
        return $this->belongsTo(Branch::class);
    }
}
