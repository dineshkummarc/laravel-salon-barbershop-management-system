<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Business extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['modules' => 'array', 'subscription_ends_at' => 'date'];
    }

    public function branches()
    {
        return $this->hasMany(Branch::class);
    }
}
