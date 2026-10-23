<?php

namespace App\Models;

class Branch extends TenantModel
{
    protected function casts(): array
    {
        return ['disabled_modules' => 'array', 'active' => 'boolean'];
    }
}
