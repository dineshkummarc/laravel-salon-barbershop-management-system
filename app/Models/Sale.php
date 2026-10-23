<?php

namespace App\Models;

class Sale extends TenantModel
{
    public bool $branchScoped = true;

    public function getDueAttribute(): int
    {
        return max(0, $this->total - $this->credited - $this->paid + $this->refunded);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function items()
    {
        return $this->hasMany(SaleItem::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function cashSession()
    {
        return $this->belongsTo(CashSession::class);
    }
}
