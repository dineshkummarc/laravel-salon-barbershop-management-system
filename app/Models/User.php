<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements FilamentUser
{
    use HasFactory, Notifiable;

    protected $fillable = ['name', 'email', 'password', 'phone', 'business_id', 'branch_id', 'role'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return ['email_verified_at' => 'datetime', 'password' => 'hashed'];
    }

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->role !== 'customer' && ($this->role === 'platform' || $this->business?->status === 'active');
    }

    public function allows(string $ability): bool
    {
        if (in_array($this->role, ['owner', 'admin', 'platform'])) {
            return true;
        }
        $rules = [
            'manager' => ['catalog', 'appointments', 'pos', 'orders', 'inventory', 'purchasing', 'crm', 'expenses', 'reports', 'hr', 'attendance', 'packages', 'marketing', 'cash'],
            'reception' => ['appointments', 'crm', 'orders'], 'cashier' => ['pos', 'orders', 'crm', 'cash'],
            'stylist' => ['appointments'], 'inventory' => ['catalog', 'inventory', 'purchasing'],
            'accounts' => ['expenses', 'accounting', 'reports', 'payroll'], 'hr' => ['hr', 'attendance', 'payroll'],
        ];

        return in_array($ability, $rules[$this->role] ?? []);
    }
}
