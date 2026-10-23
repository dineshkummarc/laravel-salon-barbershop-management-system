<?php

namespace App\Models;

class LedgerEntry extends TenantModel
{
    public bool $branchScoped = true;

    public function journal()
    {
        return $this->belongsTo(Journal::class);
    }
}
