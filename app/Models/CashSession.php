<?php

namespace App\Models;

class CashSession extends TenantModel
{
    public bool $branchScoped = true;

    protected function casts(): array
    {
        return ['opened_at' => 'datetime', 'closed_at' => 'datetime'];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
