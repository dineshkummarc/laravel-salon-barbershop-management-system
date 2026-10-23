<?php

namespace App\Models;

class MessageDelivery extends TenantModel
{
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }
}
