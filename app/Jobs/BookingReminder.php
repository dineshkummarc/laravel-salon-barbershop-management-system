<?php

namespace App\Jobs;

use App\Models\Booking;
use App\Models\ReminderDelivery;
use App\Support\BusinessContext;
use App\Support\Modules;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class BookingReminder implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public function __construct(public int $businessId, public int $bookingId) {}

    public function handle(): void
    {
        app(BusinessContext::class)->set($this->businessId, true);
        if (! Modules::enabled('appointments')) {
            return;
        }
        $booking = Booking::with('customer', 'branch', 'service')->find($this->bookingId);
        if (! $booking || $booking->status !== 'confirmed' || ! $booking->customer->email) {
            return;
        }
        $delivery = ReminderDelivery::firstOrCreate(['booking_id' => $booking->id], ['branch_id' => $booking->branch_id]);
        $claimed = ReminderDelivery::whereKey($delivery->id)->where('status', 'pending')->update(['status' => 'processing']);
        if (! $claimed) {
            return;
        }
        try {
            Mail::raw('Your '.$booking->service->name.' appointment at '.$booking->branch->name.' is at '.$booking->starts_at->timezone('Asia/Dhaka')->format('d M, h:i A').'. Contact '.$booking->branch->phone.' for changes.', fn ($m) => $m->to($booking->customer->email)->subject('Your appointment reminder'));
            $delivery->update(['status' => 'sent']);
        } catch (\Throwable $e) {
            report($e);
            $delivery->update(['status' => 'review_required']);
        }
    }
}
