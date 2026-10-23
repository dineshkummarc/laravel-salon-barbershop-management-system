<?php

namespace App\Models;

class MembershipPlan extends TenantModel
{
    protected function casts(): array
    {
        return ['active' => 'boolean'];
    }
}
