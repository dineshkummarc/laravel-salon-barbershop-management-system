<?php

namespace App\Models;

class Employee extends TenantModel
{
    public bool $branchScoped = true;

    protected function casts(): array
    {
        return ['active' => 'boolean', 'working_days' => 'array'];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
