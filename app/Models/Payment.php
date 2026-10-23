<?php

namespace App\Models;

class Payment extends TenantModel
{
    public bool $branchScoped = true;

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }
}
