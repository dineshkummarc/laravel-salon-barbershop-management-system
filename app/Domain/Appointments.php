<?php

namespace App\Domain;

use App\Models\Booking;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\Service;
use App\Models\TimeOff;
use App\Support\BusinessContext;
use App\Support\Modules;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class Appointments
{
    public function book(array $data): Booking
    {
        Modules::require('appointments', (int) $data['branch_id']);

        return DB::transaction(function () use ($data) {
            $branch = Branch::whereKey($data['branch_id'])->lockForUpdate()->firstOrFail();
            $service = Service::where('active', true)->findOrFail($data['service_id']);
            $employee = Employee::where('branch_id', $branch->id)->where('active', true)->findOrFail($data['employee_id']);
            Customer::findOrFail($data['customer_id']);
            $start = Carbon::parse($data['starts_at'], app(BusinessContext::class)->business()->timezone)->utc();
            $travel = ! empty($data['home_service']) ? 30 : 0;
            if ($travel) {
                Modules::require('home_services', $branch->id);
                if (strlen(trim($data['home_address'] ?? '')) < 10) {
                    throw ValidationException::withMessages(['home_address' => 'Enter a complete home-service address.']);
                }
            }
            $end = $start->copy()->addMinutes($service->duration + $service->buffer + $travel);
            $local = $start->copy()->timezone('Asia/Dhaka');
            $close = $local->copy()->setTimeFromTimeString($branch->closes_at);
            if ($start->isPast() || $local->format('H:i') < $branch->opens_at || $end->gt($close)) {
                throw ValidationException::withMessages(['starts_at' => 'Choose a future time within branch opening hours.']);
            }
            if ($employee->working_days && ! in_array($local->dayOfWeekIso, $employee->working_days)) {
                throw ValidationException::withMessages(['starts_at' => 'Stylist is not scheduled on this day.']);
            }
            if ($local->format('H:i') < $employee->shift_start || $end->copy()->timezone('Asia/Dhaka')->format('H:i') > $employee->shift_end) {
                throw ValidationException::withMessages(['starts_at' => 'Time falls outside the stylist shift.']);
            }
            if (TimeOff::where('employee_id', $employee->id)->where('starts_at', '<', $end)->where('ends_at', '>', $start)->exists()) {
                throw ValidationException::withMessages(['starts_at' => 'Stylist is on leave or taking a break.']);
            }
            $overlap = Booking::where('employee_id', $employee->id)->whereNotIn('status', ['cancelled', 'no_show', 'completed'])->where('starts_at', '<', $end)->where('ends_at', '>', $start)->exists();
            if ($overlap) {
                throw ValidationException::withMessages(['starts_at' => 'This stylist is already booked. Please choose another time.']);
            }
            $booking = Booking::create(['branch_id' => $branch->id, 'customer_id' => $data['customer_id'], 'employee_id' => $employee->id, 'service_id' => $service->id, 'starts_at' => $start, 'ends_at' => $end, 'price' => $service->price, 'home_service' => (bool) $travel, 'home_address' => $data['home_address'] ?? null, 'travel_minutes' => $travel, 'source' => $data['source'] ?? 'online', 'notes' => $data['notes'] ?? null]);
            Audit::record('booking.created', 'booking:'.$booking->id);

            return $booking;
        });
    }

    public function transition(Booking $booking, string $status): void
    {
        Modules::require('appointments', $booking->branch_id);
        DB::transaction(function () use ($booking, $status) {
            $b = Booking::whereKey($booking->id)->lockForUpdate()->firstOrFail();
            $allowed = ['confirmed' => ['checked_in', 'cancelled', 'no_show'], 'checked_in' => ['in_service', 'cancelled'], 'in_service' => ['completed']];
            if (! in_array($status, $allowed[$b->status] ?? [])) {
                throw ValidationException::withMessages(['status' => 'Invalid appointment transition.']);
            }
            $b->update(['status' => $status]);
            Audit::record('booking.'.$status,'booking:'.$b->id);
        });
    }
}
