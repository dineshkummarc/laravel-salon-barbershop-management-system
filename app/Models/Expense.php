<?php

namespace App\Models;

class Expense extends TenantModel
{
    public bool $branchScoped = true;

    protected function casts(): array
    {
        return ['date' => 'date'];
    }
}
