<?php

namespace App\Models;

class Payroll extends TenantModel
{
    public bool $branchScoped = true;

    protected function casts(): array
    {
        return ['paid_at' => 'datetime'];
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
