<?php

namespace App\Domain;

use App\Models\Attendance;
use App\Models\Booking;
use App\Models\Branch;
use App\Models\CashSession;
use App\Models\CommissionAdjustment;
use App\Models\Customer;
use App\Models\CustomerPackage;
use App\Models\Employee;
use App\Models\Expense;
use App\Models\LedgerEntry;
use App\Models\PackagePlan;
use App\Models\PackageRedemption;
use App\Models\Payroll;
use App\Models\SaleItem;
use App\Support\Modules;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class Operations
{
    public function expense(Expense $expense): void
    {
        Modules::require('expenses', $expense->branch_id);
        DB::transaction(function () use ($expense) {
            $e = Expense::whereKey($expense->id)->lockForUpdate()->firstOrFail();
            if ($e->status !== 'draft') {
                throw ValidationException::withMessages(['status' => 'Expense already posted.']);
            }
            app(Ledger::class)->post($e->branch_id, 'expense:'.$e->id, $e->description, ['expenses' => $e->amount, $e->method === 'cash' ? 'cash' : 'bank' => -$e->amount]);
            $e->update(['status' => 'posted']);
            Audit::record('expense.posted', 'expense:'.$e->id);
        });
    }

    public function payroll(int $employee, string $month, int $allowance = 0, int $deduction = 0): Payroll
    {
        Modules::require('payroll');

        return DB::transaction(function () use ($employee, $month, $allowance, $deduction) {
            $e = Employee::whereKey($employee)->lockForUpdate()->firstOrFail();
            $start = Carbon::createFromFormat('!Y-m', $month, 'Asia/Dhaka')->startOfMonth()->utc();
            $end = $start->copy()->timezone('Asia/Dhaka')->endOfMonth()->utc();
            if (Payroll::where('employee_id', $e->id)->where('month', $month)->exists()) {
                throw ValidationException::withMessages(['month' => 'Payroll already generated for this employee and month.']);
            }
            $commission = SaleItem::where('employee_id', $e->id)->whereBetween('created_at', [$start, $end])->selectRaw('COALESCE(SUM(commission - commission_reversed),0) as net')->value('net') + CommissionAdjustment::where('employee_id', $e->id)->where('month', $month)->sum('amount');
            $commission = max(0, $commission);
            if ($allowance < 0 || $deduction < 0 || $deduction > $e->salary + $commission + $allowance) {
                throw ValidationException::withMessages(['deduction' => 'Invalid payroll adjustments.']);
            }

            return Payroll::create(['branch_id' => $e->branch_id, 'employee_id' => $e->id, 'month' => $month, 'salary' => $e->salary, 'commission' => $commission, 'allowance' => $allowance, 'deduction' => $deduction, 'total' => $e->salary + $commission + $allowance - $deduction]);
        });
    }

    public function payPayroll(Payroll $payroll): void
    {
        Modules::require('payroll', $payroll->branch_id);
        DB::transaction(function () use ($payroll) {
            $p = Payroll::whereKey($payroll->id)->lockForUpdate()->firstOrFail();
            if ($p->status !== 'draft') {
                throw ValidationException::withMessages(['status' => 'Payroll is locked.']);
            }
            app(Ledger::class)->post($p->branch_id, 'payroll:'.$p->id, 'Payroll '.$p->month, ['payroll_expense' => $p->total, 'bank' => -$p->total]);
            $p->update(['status' => 'paid', 'paid_at' => now()]);
            Audit::record('payroll.paid', 'payroll:'.$p->id);
        });
    }

    public function attendance(array $data): Attendance
    {
        Modules::require('attendance', (int) $data['branch_id']);

        return DB::transaction(function () use ($data) {
            $employee = Employee::where('branch_id', $data['branch_id'])->lockForUpdate()->findOrFail($data['employee_id']);
            $date = Carbon::parse($data['check_in'])->timezone('Asia/Dhaka')->toDateString();
            if (Payroll::where('employee_id', $employee->id)->where('month', substr($date, 0, 7))->where('status', 'paid')->exists()) {
                throw ValidationException::withMessages(['date' => 'Paid payroll locks attendance for this month.']);
            }
            if (! empty($data['external_id']) && ($a = Attendance::where('external_id', $data['external_id'])->first())) {
                return $a;
            }
            if (! empty($data['check_out']) && Carbon::parse($data['check_out'])->lte(Carbon::parse($data['check_in']))) {
                throw ValidationException::withMessages(['check_out' => 'Check-out must follow check-in.']);
            }
            $values = ['branch_id' => $employee->branch_id, 'check_in' => $data['check_in'], 'check_out' => $data['check_out'] ?? null, 'source' => $data['source'] ?? 'manual', 'external_id' => $data['external_id'] ?? null];
            $attendance = Attendance::where('employee_id', $employee->id)->whereDate('date', $date)->first();
            if ($attendance) {
                $attendance->update($values);
            } else {
                $attendance = Attendance::create($values + ['employee_id' => $employee->id, 'date' => $date]);
            }
            Audit::record('attendance.recorded', 'attendance:'.$attendance->id);

            return $attendance;
        });
    }

    public function issuePackage(array $data): CustomerPackage
    {
        Modules::require('packages', (int) $data['branch_id']);

        return DB::transaction(function () use ($data) {
            Branch::findOrFail($data['branch_id']);
            Customer::findOrFail($data['customer_id']);
            $plan = PackagePlan::where('active', true)->findOrFail($data['package_plan_id']);
            $p = CustomerPackage::create(['branch_id' => $data['branch_id'], 'customer_id' => $data['customer_id'], 'package_plan_id' => $plan->id, 'remaining' => $plan->uses, 'price' => $plan->price, 'expires_at' => now()->addDays($plan->valid_days)]);
            app(Ledger::class)->post($p->branch_id, 'package:'.$p->id, 'Prepaid package sold', ['cash' => $p->price, 'package_liability' => -$p->price]);
            Audit::record('package.issued', 'package:'.$p->id);

            return $p;
        });
    }

    public function redeem(CustomerPackage $package, Booking $booking): void
    {
        Modules::require('packages', $package->branch_id);
        DB::transaction(function () use ($package, $booking) {
            $p = CustomerPackage::whereKey($package->id)->lockForUpdate()->firstOrFail();
            $b = Booking::whereKey($booking->id)->lockForUpdate()->firstOrFail();
            if ($p->remaining < 1 || $p->expires_at->endOfDay()->isPast() || $b->customer_id !== $p->customer_id || $b->service_id !== $p->packagePlan->service_id || $b->status !== 'completed' || $b->sale()->exists() || PackageRedemption::where('booking_id', $b->id)->exists()) {
                throw ValidationException::withMessages(['package' => 'This package cannot be redeemed against this appointment.']);
            }
            $uses = $p->packagePlan->uses;
            $used = $uses - $p->remaining;
            $value = intdiv($p->price * ($used + 1), $uses) - intdiv($p->price * $used, $uses);
            $r = PackageRedemption::create(['branch_id' => $p->branch_id, 'customer_package_id' => $p->id, 'booking_id' => $b->id, 'value' => $value]);
            $p->decrement('remaining');
            $cost = app(StockControl::class)->consume($p->branch_id, $b->service_id, 1, 'redemption:'.$r->id);
            if ($cost) {
                app(Ledger::class)->post($p->branch_id, 'redemption:'.$r->id.':cost', 'Package consumables', ['cost_of_goods' => $cost, 'inventory' => -$cost]);
            }
            app(Ledger::class)->post($p->branch_id, 'redemption:'.$r->id, 'Package service earned', ['package_liability' => $value, 'revenue' => -$value]);
            Audit::record('package.redeemed', 'package:'.$p->id, ['booking' => $b->id]);
        });
    }

    public function closeRegister(CashSession $session, int $counted): void
    {
        DB::transaction(function () use ($session, $counted) {
            $s = CashSession::whereKey($session->id)->lockForUpdate()->firstOrFail();
            if ($s->closed_at || $counted < 0) {
                throw ValidationException::withMessages(['cash' => 'Register already closed or invalid cash count.']);
            }
            $net = LedgerEntry::where('branch_id', $s->branch_id)->where('account', 'cash')->where('created_at', '>=', $s->opened_at)->whereHas('journal', fn ($q) => $q->where('user_id', $s->user_id))->selectRaw('COALESCE(SUM(debit-credit),0) as net')->value('net');
            $s->update(['closed_at' => now(), 'counted_cash' => $counted, 'expected_cash' => $s->opening_float + $net]);
            Audit::record('register.closed','register:'.$s->id,['variance' => $counted - $s->opening_float - $net]);
        });
    }
}
