<?php

namespace App\Domain;

use App\Models\Branch;
use App\Models\Coupon;
use App\Models\Customer;
use App\Models\GiftCard;
use App\Models\Membership;
use App\Models\MembershipPlan;
use App\Models\Review;
use App\Models\Sale;
use App\Support\Modules;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class Retention
{
    public function gift(array $data): GiftCard
    {
        Modules::require('gift_cards', (int) $data['branch_id']);

        return DB::transaction(function () use ($data) {
            Branch::findOrFail($data['branch_id']);
            if (! empty($data['customer_id'])) {
                Customer::findOrFail($data['customer_id']);
            }
            if ($data['issued_value'] < 100) {
                throw ValidationException::withMessages(['issued_value' => 'Minimum value is BDT 1.']);
            }
            $card = GiftCard::create(['branch_id' => $data['branch_id'], 'customer_id' => $data['customer_id'] ?? null, 'code' => strtoupper(Str::random(20)), 'issued_value' => $data['issued_value'], 'balance' => $data['issued_value'], 'expires_at' => $data['expires_at']]);
            app(Ledger::class)->post($card->branch_id, 'gift:'.$card->id, 'Gift card cash sale', ['cash' => $card->issued_value, 'gift_liability' => -$card->issued_value]);
            Audit::record('gift.issued', 'gift:'.$card->id);

            return $card;
        });
    }

    public function membership(array $data): Membership
    {
        Modules::require('memberships', (int) $data['branch_id']);

        return DB::transaction(function () use ($data) {
            Branch::findOrFail($data['branch_id']);
            $customer = Customer::whereKey($data['customer_id'])->lockForUpdate()->firstOrFail();
            $plan = MembershipPlan::where('active', true)->findOrFail($data['membership_plan_id']);
            if (Membership::where('customer_id', $customer->id)->where('status', 'active')->whereDate('ends_at', '>=', today())->exists()) {
                throw ValidationException::withMessages(['customer_id' => 'Customer already has an active membership.']);
            }
            $membership = Membership::create(['branch_id' => $data['branch_id'], 'customer_id' => $customer->id, 'membership_plan_id' => $plan->id, 'starts_at' => today(), 'ends_at' => today()->addDays($plan->days - 1), 'discount_percent' => $plan->discount_percent, 'price' => $plan->price]);
            app(Ledger::class)->post($membership->branch_id, 'membership:'.$membership->id, 'Membership cash sale', ['cash' => $plan->price, 'membership_revenue' => -$plan->price]);
            Audit::record('membership.issued', 'membership:'.$membership->id);

            return $membership;
        });
    }

    /** Returns automatic discount; coupons and membership benefits never stack. */
    public function discount(int $subtotal, ?int $customer, ?string $code): array
    {
        $membershipPercent = 0;
        if ($customer && Modules::enabled('memberships')) {
            $membershipPercent = (int) (Membership::where('customer_id', $customer)->where('status', 'active')->whereDate('starts_at', '<=', today())->whereDate('ends_at', '>=', today())->max('discount_percent') ?? 0);
        }
        $coupon = null;
        $percent = $membershipPercent;
        if ($code) {
            Modules::require('marketing');
            $coupon = Coupon::where('code', strtoupper(trim($code)))->where('active', true)->lockForUpdate()->first();
            if (! $coupon || $coupon->expires_at->endOfDay()->isPast() || $coupon->used >= $coupon->usage_limit) {
                throw ValidationException::withMessages(['coupon_code' => 'This coupon is invalid, expired, or fully used.']);
            }
            $percent = max($percent, $coupon->percent);
            $coupon->increment('used');
        }

        return [intdiv($subtotal * $percent, 100), $coupon?->code];
    }

    public function review(int $saleId, int $customer, int $rating, string $body): Review
    {
        Modules::require('reviews');

        return DB::transaction(function () use ($saleId, $customer, $rating, $body) {
            $sale = Sale::where('customer_id', $customer)->whereKey($saleId)->lockForUpdate()->firstOrFail();
            if ($sale->status !== 'completed' || $sale->paid < $sale->total || $rating < 1 || $rating > 5) {
                throw ValidationException::withMessages(['review' => 'Only completed, fully paid orders can be reviewed.']);
            }

            return Review::create(['customer_id' => $customer, 'sale_id' => $sale->id, 'rating' => $rating, 'body' => $body]);
        });
    }
}
