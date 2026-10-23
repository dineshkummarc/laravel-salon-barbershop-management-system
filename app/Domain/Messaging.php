<?php

namespace App\Domain;

use App\Jobs\DeliverCampaign;
use App\Models\Campaign;
use App\Models\Customer;
use App\Models\MessageDelivery;
use App\Support\BusinessContext;
use App\Support\Modules;
use Illuminate\Support\Facades\DB;

class Messaging
{
    public function campaign(Campaign $record): void
    {
        Modules::require('marketing');
        DB::transaction(function () use ($record) {
            $campaign = Campaign::whereKey($record->id)->lockForUpdate()->firstOrFail();
            abort_unless($campaign->status === 'draft', 422, 'Campaign is already queued.');
            $campaign->update(['status' => 'queued']);
            foreach (Customer::where('marketing_consent', true)->whereNotNull('email')->get() as $c) {
                $d = MessageDelivery::firstOrCreate(['campaign_id' => $campaign->id, 'customer_id' => $c->id]);
                DeliverCampaign::dispatch(app(BusinessContext::class)->id(), $d->id)->afterCommit();
            }
            Audit::record('campaign.queued', 'campaign:'.$campaign->id);
        });
    }
}
