<?php

namespace App\Models;

class AttendanceEvent extends TenantModel
{
    public bool $branchScoped = true;

    protected function casts(): array
    {
        return ['punched_at' => 'datetime', 'payload' => 'array'];
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
