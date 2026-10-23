<?php

namespace App\Models;

class CustomerPackage extends TenantModel
{
    public bool $branchScoped = true;

    protected function casts(): array
    {
        return ['expires_at' => 'date'];
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function packagePlan()
    {
        return $this->belongsTo(PackagePlan::class);
    }
}
