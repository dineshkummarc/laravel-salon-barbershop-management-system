<?php

namespace App\Models;

class AuditLog extends TenantModel
{
    protected function casts(): array
    {
        return ['changes' => 'array'];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
