<?php

namespace App\Domain;

use App\Models\Booking;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\Service;
use App\Models\TimeOff;
use App\Support\Modules;
use Carbon\Carbon;

class Availability
{
    public function slots(int $branchId, int $serviceId, int $employeeId, string $date): array
    {
        Modules::require('appointments', $branchId);
        $branch = Branch::where('active', true)->findOrFail($branchId);
        $service = Service::where('active', true)->findOrFail($serviceId);
        $employee = Employee::where('branch_id', $branchId)->where('active', true)->findOrFail($employeeId);
        $day = Carbon::parse($date, 'Asia/Dhaka')->startOfDay();
        if ($employee->working_days && ! in_array($day->dayOfWeekIso, $employee->working_days)) {
            return [];
        }
        $start = $day->copy()->setTimeFromTimeString(max($branch->opens_at, $employee->shift_start));
        $close = $day->copy()->setTimeFromTimeString(min($branch->closes_at, $employee->shift_end));
        $slots = [];
        $bookings = Booking::where('employee_id', $employeeId)->whereNotIn('status', ['cancelled', 'no_show', 'completed'])->whereBetween('starts_at', [$day->copy()->utc(), $day->copy()->endOfDay()->utc()])->get();
        $leave = TimeOff::where('employee_id', $employeeId)->where('starts_at', '<', $day->copy()->endOfDay()->utc())->where('ends_at', '>', $day->copy()->utc())->get();
        for ($candidate = $start->copy(); $candidate->copy()->addMinutes($service->duration + $service->buffer)->lte($close); $candidate->addMinutes(15)) {
            $end = $candidate->copy()->addMinutes($service->duration + $service->buffer);
            if ($candidate->isPast()) {
                continue;
            }
            $overlap = fn ($b) => $b->starts_at->lt($end) && $b->ends_at->gt($candidate);
            if (! $bookings->contains($overlap) && ! $leave->contains($overlap)) {
                $slots[] = ['value' => $candidate->format('Y-m-d\TH:i'), 'label' => $candidate->format('h:i A')];
            }
        }

return $slots;
    }
}
