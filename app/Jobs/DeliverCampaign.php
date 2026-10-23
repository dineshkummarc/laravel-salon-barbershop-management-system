<?php

namespace App\Jobs;

use App\Models\MessageDelivery;
use App\Support\BusinessContext;
use App\Support\Modules;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

class DeliverCampaign implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public function __construct(public int $businessId, public int $deliveryId) {}

    public function handle(): void
    {
        app(BusinessContext::class)->set($this->businessId, true);
        if (! Modules::enabled('marketing')) {
            return;
        }
        $delivery = DB::transaction(function () {
            $d = MessageDelivery::whereKey($this->deliveryId)->lockForUpdate()->firstOrFail();
            if ($d->status !== 'pending') {
                return null;
            }
            $d->update(['status' => 'processing']);

            return $d;
        });
        if (! $delivery) {
            return;
        }
        $c = $delivery->customer;
        if (! $c->marketing_consent || ! $c->email) {
            $delivery->update(['status' => 'skipped']);

            return;
        }
        try {
            $unsubscribe = URL::signedRoute('unsubscribe', ['token' => Crypt::encryptString($this->businessId.':'.$c->id)]);
            Mail::send('mail.campaign', ['campaign' => $delivery->campaign, 'unsubscribe' => $unsubscribe], fn ($m) => $m->to($c->email)->subject($delivery->campaign->title));
            $delivery->update(['status' => 'sent']);
            $delivery->campaign->increment('sent');
        } catch (\Throwable $e) {
            report($e);
            $delivery->update(['status' => 'review_required', 'error' => 'Transport failed or delivery outcome is uncertain. Review before resending.']);
        }
        $campaign = $delivery->campaign;
        if (! MessageDelivery::where('campaign_id', $campaign->id)->whereIn('status', ['pending', 'processing'])->exists()) {
            $campaign->update(['status' => 'processed']);
        }
    }
}
