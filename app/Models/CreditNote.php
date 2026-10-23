<?php

namespace App\Models;

class CreditNote extends TenantModel
{
    public bool $branchScoped = true;

    protected function casts(): array
    {
        return ['items' => 'array'];
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }
}
