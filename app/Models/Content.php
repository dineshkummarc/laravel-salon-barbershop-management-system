<?php

namespace App\Models;

class Content extends TenantModel
{
    protected function casts(): array
    {
        return ['published' => 'boolean'];
    }
}
