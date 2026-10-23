<?php

namespace App\Models;

class AttendanceDevice extends TenantModel
{
    public bool $branchScoped = true;

    protected $hidden = ['credentials'];

    protected function casts(): array
    {
        return ['employee_map' => 'array', 'credentials' => 'encrypted:array', 'last_sync_at' => 'datetime'];
    }
}
