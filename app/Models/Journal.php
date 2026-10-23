<?php

namespace App\Models;

class Journal extends TenantModel
{
    public bool $branchScoped = true;

    public function entries()
    {
        return $this->hasMany(LedgerEntry::class);
    }
}
