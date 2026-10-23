<?php

namespace App\Models;

class TimeOff extends TenantModel
{
    public bool $branchScoped = true;

    protected function casts(): array
    {
        return ['starts_at' => 'datetime', 'ends_at' => 'datetime'];
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
