<?php

namespace App\Models;

class Customer extends TenantModel
{
    protected function casts(): array
    {
        return ['marketing_consent' => 'boolean'];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }
}
