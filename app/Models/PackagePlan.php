<?php

namespace App\Models;

use Illuminate\Validation\ValidationException;

class PackagePlan extends TenantModel
{
    protected static function booted(): void
    {
        parent::booted();
        static::updating(function ($plan) {
            if ($plan->isDirty(['uses', 'service_id']) && CustomerPackage::where('package_plan_id', $plan->id)->exists()) {
                throw ValidationException::withMessages(['uses' => 'Issued packages lock the service and use count. Create a new plan for changed terms.']);
            }
        });
    }

    protected function casts(): array
    {
        return ['active' => 'boolean'];
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }
}
