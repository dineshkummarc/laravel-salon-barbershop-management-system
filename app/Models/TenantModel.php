<?php

namespace App\Models;

use App\Domain\Audit;
use App\Support\BusinessContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

abstract class TenantModel extends Model
{
    protected $guarded = ['id'];

    public bool $branchScoped = false;

    protected static function booted(): void
    {
        static::addGlobalScope('business', function (Builder $q) {
            $context = app(BusinessContext::class);
            $id = $context->id();
            $q->where($q->getModel()->qualifyColumn('business_id'), $id ?? 0);
            if (! $context->public && $q->getModel() instanceof Booking && auth()->user()?->role === 'stylist') {
                $q->whereIn('employee_id', Employee::where('user_id', auth()->id())->select('id'));
            }
            if ($q->getModel()->branchScoped && $context->branchId()) {
                $q->where($q->getModel()->qualifyColumn('branch_id'), $context->branchId());
            }
        });
        static::creating(function (TenantModel $model) {
            $context = app(BusinessContext::class);
            $model->business_id ??= $context->id();
            if ((int) $model->business_id !== (int) $context->id()) {
                abort(403, 'Cross-business write rejected.');
            }
            if (isset($model->branch_id)) {
                abort_unless(Branch::whereKey($model->branch_id)->exists(), 403, 'Invalid branch.');
            }
            if (isset($model->branch_id) && $context->branchId() && (int) $model->branch_id !== $context->branchId()) {
                abort(403, 'Branch access denied.');
            }
        });
        static::updated(function (TenantModel $model) {
            if (in_array(class_basename($model), ['Branch', 'Employee', 'Service', 'Product', 'Customer', 'PackagePlan', 'MembershipPlan', 'Content', 'Coupon', 'AttendanceDevice'])) {
                $fields = array_values(array_diff(array_keys($model->getChanges()), ['updated_at', 'credentials', 'notes', 'body', 'description']));
                if ($fields) {
                    Audit::record('record.updated', $model->getTable().':'.$model->id, ['fields' => $fields]);
                }
            }
        });
        static::updating(function (TenantModel $model) {
            abort_if($model->isDirty('business_id'), 403, 'Business ownership is immutable.');
            $context = app(BusinessContext::class);
            if ($model->branchScoped) {
                abort_unless(Branch::whereKey($model->branch_id)->exists(), 403, 'Invalid branch.');
                if ($context->branchId()) {
                    abort_unless((int) $model->branch_id === $context->branchId(), 403, 'Branch access denied.');
                }
            }
        });
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function business()
    {
        return $this->belongsTo(Business::class);
    }
}
