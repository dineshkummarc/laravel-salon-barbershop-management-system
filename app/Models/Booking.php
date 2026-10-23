<?php

namespace App\Models;

class Booking extends TenantModel
{
    public bool $branchScoped = true;

    protected function casts(): array
    {
        return ['starts_at' => 'datetime', 'ends_at' => 'datetime'];
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function sale()
    {
        return $this->hasOne(Sale::class);
    }
}
