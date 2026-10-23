<?php

namespace App\Models;

class CommissionAdjustment extends TenantModel
{
    public bool $branchScoped = true;

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }
}
