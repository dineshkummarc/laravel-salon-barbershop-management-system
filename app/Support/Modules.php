<?php

namespace App\Support;

use App\Models\Branch;
use Illuminate\Validation\ValidationException;

class Modules
{
    public const ALL = [
        'website' => 'Public website', 'services' => 'Service catalog', 'appointments' => 'Appointments & walk-ins',
        'pos' => 'Point of sale', 'products' => 'Product catalog', 'ecommerce' => 'Online orders', 'inventory' => 'Inventory',
        'purchasing' => 'Purchasing & suppliers', 'crm' => 'Customer relationships', 'packages' => 'Prepaid packages',
        'expenses' => 'Expenses', 'accounting' => 'Accounting', 'hr' => 'Employees', 'attendance' => 'Attendance',
        'payroll' => 'Payroll & commissions', 'memberships' => 'Memberships', 'gift_cards' => 'Gift cards', 'loyalty' => 'Loyalty rewards', 'marketing' => 'Email campaigns', 'reviews' => 'Verified reviews', 'home_services' => 'Home services', 'content' => 'Website content', 'reports' => 'Reports', 'ai' => 'AI assistant',
    ];

    public const DEPENDENCIES = ['home_services' => ['appointments'], 'reviews' => ['crm'], 'marketing' => ['crm'], 'loyalty' => ['crm'], 'gift_cards' => ['accounting', 'crm'], 'memberships' => ['accounting', 'crm'], 'ecommerce' => ['website', 'products', 'inventory'], 'pos' => ['services'], 'appointments' => ['services', 'hr'], 'purchasing' => ['inventory', 'products'], 'inventory' => ['products'], 'payroll' => ['hr', 'accounting'], 'attendance' => ['hr'], 'packages' => ['services', 'crm', 'accounting']];

    public static function enabled(?string $module, ?int $branchId = null): bool
    {
        if (! $module) {
            return true;
        }
        $context = app(BusinessContext::class);
        $business = $context->business();
        if (! $business || $business->status !== 'active' || ! in_array($module, self::entitlements($business->plan)) || ! in_array($module, $business->modules ?? [])) {
            return false;
        }
        $branch = $branchId ?? $context->branchId();
        if ($branch && in_array($module, Branch::find($branch)?->disabled_modules ?? [])) {
            return false;
        }

        return true;
    }

    public static function require(string $module, ?int $branch = null): void
    {
        abort_unless(self::enabled($module, $branch), 403, 'This module is disabled for this business or branch.');
    }

    public static function entitlements(string $plan): array
    {
        return $plan === 'basic' ? ['website', 'services', 'pos', 'crm', 'hr', 'content', 'reports'] : array_keys(self::ALL);
    }

    public static function validate(array $modules, ?string $plan = null): void
    {
        $plan ??= app(BusinessContext::class)->business()?->plan ?? 'professional';
        if (array_diff($modules, self::entitlements($plan))) {
            throw ValidationException::withMessages(['modules' => 'Some modules are unavailable on this plan. Upgrade the subscription first.']);
        }
        foreach ($modules as $m) {
            abort_unless(isset(self::ALL[$m]), 422, 'Unknown module.');
            foreach (self::DEPENDENCIES[$m] ?? [] as $dependency) {
                if (! in_array($dependency, $modules)) {
                    throw ValidationException::withMessages(['modules' => self::ALL[$m].' requires '.self::ALL[$dependency].'.']);
                }
            }
        }
    }
}
