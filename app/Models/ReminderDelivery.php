<?php

namespace App\Models;

class ReminderDelivery extends TenantModel
{
    public bool $branchScoped = true;

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }
}
