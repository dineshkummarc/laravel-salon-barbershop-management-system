<?php

namespace App\Models;

class PlatformInvoice extends TenantModel
{
    protected function casts(): array
    {
        return ['period_start' => 'date', 'period_end' => 'date'];
    }
}
