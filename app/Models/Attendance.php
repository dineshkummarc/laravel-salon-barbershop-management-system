<?php

namespace App\Models;

class Attendance extends TenantModel
{
    public bool $branchScoped = true;

    protected function casts(): array
    {
        return ['date' => 'date', 'check_in' => 'datetime', 'check_out' => 'datetime'];
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
