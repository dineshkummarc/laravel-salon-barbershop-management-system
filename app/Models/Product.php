<?php

namespace App\Models;

use Illuminate\Validation\ValidationException;

class Product extends TenantModel
{
    protected static function booted(): void
    {
        parent::booted();
        static::updating(function ($product) {
            if ($product->isDirty('cost') && Stock::where('product_id', $product->id)->where('quantity', '<>', 0)->exists()) {
                throw ValidationException::withMessages(['cost' => 'Standard cost cannot change while stock is held. Clear stock before changing its standard cost.']);
            }
        });
    }

    protected function casts(): array
    {
        return ['active' => 'boolean'];
    }

    public function stocks()
    {
        return $this->hasMany(Stock::class);
    }
}
