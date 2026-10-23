<?php

namespace App\Models;

class PackageRedemption extends TenantModel
{
    public bool $branchScoped = true;

    public function customerPackage()
    {
        return $this->belongsTo(CustomerPackage::class);
    }

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }
}
