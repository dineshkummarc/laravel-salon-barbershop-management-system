<?php

namespace App\Domain;

use App\Models\Booking;
use App\Models\Branch;
use App\Models\CashSession;
use App\Models\CommissionAdjustment;
use App\Models\Coupon;
use App\Models\CreditNote;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\GiftCard;
use App\Models\LoyaltyEntry;
use App\Models\PackageRedemption;
use App\Models\Payment;
use App\Models\Payroll;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Service;
use App\Support\BusinessContext;
use App\Support\Modules;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class Checkout
{
    private function fail(string $message): never
    {
        throw ValidationException::withMessages(['sale' => $message]);
    }

    public function create(array $data): Sale
    {
        $channel = $data['channel'] ?? 'pos';
        Modules::require($channel === 'online' ? 'ecommerce' : 'pos', (int) $data['branch_id']);

        return DB::transaction(function () use ($data, $channel) {
            Branch::whereKey($data['branch_id'])->where('active', true)->lockForUpdate()->firstOrFail();
            if ($sale = Sale::where('idempotency_key', $data['idempotency_key'])->first()) {
                return $sale;
            }
            if (! empty($data['customer_id'])) {
                Customer::findOrFail($data['customer_id']);
            }
            $booking = null;
            if (! empty($data['booking_id'])) {
                $booking = Booking::where('branch_id', $data['branch_id'])->lockForUpdate()->findOrFail($data['booking_id']);
                if ($booking->status !== 'completed' || $booking->sale()->exists()) {
                    $this->fail('Only completed, unbilled appointments can be billed.');
                }
                if ((int) ($data['customer_id'] ?? 0) !== $booking->customer_id) {
                    $this->fail('Appointment customer must match the sale.');
                }
            }
            $lines = [];
            $subtotal = 0;
            $commission = 0;
            if (empty($data['items']) || count($data['items']) > 100) {
                $this->fail('Add between 1 and 100 items.');
            }
            foreach ($data['items'] as $line) {
                $type = $line['type'];
                if ($type === 'product') {
                    Modules::require('inventory', (int) $data['branch_id']);
                } $qty = filter_var($line['quantity'], FILTER_VALIDATE_INT);
                if (! $qty || $qty < 1 || $qty > 1000 || ! in_array($type, ['product', 'service'])) {
                    $this->fail('Invalid line item.');
                }
                Modules::require($type === 'product' ? 'products' : 'services', (int) $data['branch_id']);
                $model = ($type === 'product' ? Product::query() : Service::query())->where('active', true)->findOrFail($line['id']);
                $staff = null;
                if (! empty($line['employee_id'])) {
                    $staff = Employee::where('branch_id', $data['branch_id'])->where('active', true)->findOrFail($line['employee_id']);
                }
                $unitPrice = $booking && $type === 'service' && $booking->service_id === $model->id ? $booking->price : $model->price;
                $lineCommission = $type === 'service' && $staff ? intdiv($unitPrice * $qty * $staff->commission_basis_points, 10000) : 0;
                $total = $unitPrice * $qty;
                $subtotal += $total;
                $commission += $lineCommission;
                $lines[] = ['branch_id' => $data['branch_id'], $type.'_id' => $model->id, 'employee_id' => $staff?->id, 'name' => $model->name, 'quantity' => $qty, 'unit_price' => $unitPrice, 'unit_cost' => $type === 'product' ? $model->cost : 0, 'total' => $total, 'commission' => $lineCommission];
            }
            if ($booking && PackageRedemption::where('booking_id', $booking->id)->exists()) {
                $this->fail('Appointment was paid with a package.');
            }
            if ($booking && (count($lines) !== 1 || ($lines[0]['service_id'] ?? null) !== $booking->service_id || $lines[0]['quantity'] !== 1)) {
                $this->fail('Appointment billing must contain its service exactly once.');
            }
            [$automaticDiscount,$coupon] = app(Retention::class)->discount($subtotal, $data['customer_id'] ?? null, $data['coupon_code'] ?? null);
            $manualDiscount = (int) ($data['discount'] ?? 0);
            $points = (int) ($data['points_redeemed'] ?? 0);
            $tip = (int) ($data['tip'] ?? 0);
            if ($points < 0 || $tip < 0 || $tip > 10000000) {
                $this->fail('Invalid rewards or tip.');
            }
            if ($points) {
                Modules::require('loyalty');
                if (empty($data['customer_id'])) {
                    $this->fail('Select a customer to redeem points.');
                }
                $customer = Customer::whereKey($data['customer_id'])->lockForUpdate()->firstOrFail();
                if ($points > $customer->loyalty_points || $points * 100 > intdiv($subtotal, 5)) {
                    $this->fail('Rewards exceed the balance or 20% of subtotal.');
                }
                $customer->decrement('loyalty_points', $points);
            }
            $discount = max($manualDiscount, $automaticDiscount) + $points * 100;
            if ($discount < 0 || $discount > $subtotal) {
                $this->fail('Invalid discount.');
            }
            if ($manualDiscount && ! auth()->user()?->allows('discount')) {
                $this->fail('Only an owner or administrator can discount sales.');
            }
            $tax = intdiv(($subtotal - $discount) * app(BusinessContext::class)->business()->tax_basis_points + 5000, 10000);
            $session = null;
            if ($channel === 'pos' && auth()->check()) {
                $session = CashSession::where('branch_id', $data['branch_id'])->where('user_id', auth()->id())->whereNull('closed_at')->first();
            }
            $sale = Sale::create(['branch_id' => $data['branch_id'], 'customer_id' => $data['customer_id'] ?? null, 'booking_id' => $booking?->id, 'cash_session_id' => $session?->id, 'number' => 'HB-'.strtoupper(Str::random(10)), 'token' => Str::uuid(), 'idempotency_key' => $data['idempotency_key'], 'channel' => $channel, 'status' => $channel === 'online' ? 'pending' : 'completed', 'fulfillment' => $channel === 'online' ? 'pending' : 'collected', 'delivery_method' => $data['delivery_method'] ?? 'pickup', 'delivery_address' => $data['delivery_address'] ?? null, 'subtotal' => $subtotal, 'discount' => $discount, 'tax' => $tax, 'total' => $subtotal - $discount + $tax + $tip, 'commission' => $commission, 'points_redeemed' => $points, 'coupon_code' => $coupon, 'tip' => $tip]);
            $cost = 0;
            foreach ($lines as $line) {
                if (isset($line['service_id'])) {
                    $consumed = app(StockControl::class)->consume($sale->branch_id, $line['service_id'], $line['quantity'], 'sale:'.$sale->id);
                    $line['unit_cost'] = intdiv($consumed, $line['quantity']);
                    $cost += $consumed;
                }
                $sale->items()->create($line);
                if (isset($line['product_id'])) {
                    app(Inventory::class)->move($sale->branch_id, $line['product_id'], -$line['quantity'], 'sale', 'sale:'.$sale->id);
                    $cost += $line['unit_cost'] * $line['quantity'];
                }
            }
            app(Ledger::class)->post($sale->branch_id, 'sale:'.$sale->id, 'Sale '.$sale->number, ['receivable' => $sale->total, 'revenue' => -($subtotal - $discount), 'tax_payable' => -$tax, 'tips_payable' => -$tip]);
            if ($cost) {
                app(Ledger::class)->post($sale->branch_id, 'sale:'.$sale->id.':cost', 'Cost of goods sold', ['cost_of_goods' => $cost, 'inventory' => -$cost]);
            }
            if ($sale->customer_id && Modules::enabled('loyalty')) {
                $earned = intdiv($sale->total - $sale->tip, 10000);
                $sale->customer->increment('loyalty_points', $earned);
                LoyaltyEntry::create(['branch_id' => $sale->branch_id, 'customer_id' => $sale->customer_id, 'sale_id' => $sale->id, 'points' => $earned - $points, 'reason' => 'sale']);
            }
            foreach ($data['payments'] ?? [] as $payment) {
                $this->pay($sale, $payment['method'], (int) $payment['amount'], $data['idempotency_key'].':'.($payment['key'] ?? Str::uuid()), $payment['reference'] ?? null);
            }
            Audit::record('sale.created', 'sale:'.$sale->id, ['channel' => $channel, 'total' => $sale->total]);

            return $sale->fresh();
        });
    }

    public function pay(Sale $sale, string $method, int $amount, string $key, ?string $reference = null): Payment
    {
        Modules::require($sale->channel === 'online' ? 'ecommerce' : 'pos', $sale->branch_id);

        return DB::transaction(function () use ($sale, $method, $amount, $key, $reference) {
            $s = Sale::whereKey($sale->id)->lockForUpdate()->firstOrFail();
            if ($p = Payment::where('idempotency_key', $key)->first()) {
                if ($p->sale_id !== $s->id) {
                    $this->fail('Idempotency key belongs to another sale.');
                }

return $p;
            }
            if (! in_array($method, ['cash', 'bank', 'bkash_manual', 'nagad_manual', 'rocket_manual', 'demo', 'gift_card']) || ($method === 'demo' && ! config('salon.demo'))) {
                $this->fail('Unsupported payment method.');
            }
            if (in_array($s->status, ['cancelled', 'refunded']) || $amount <= 0 || $amount > $s->total - $s->credited - $s->paid + $s->refunded) {
                $this->fail('Payment exceeds outstanding balance or sale is closed.');
            }
            if (str_contains($method, 'manual') && ! $reference) {
                $this->fail('A verified manual transfer reference is required.');
            }
            $gift = null;
            if ($method === 'gift_card') {
                Modules::require('gift_cards', $s->branch_id);
                $gift = GiftCard::where('code', strtoupper((string) $reference))->lockForUpdate()->first();
                if (! $gift || $gift->expires_at->endOfDay()->isPast() || $gift->balance < $amount || ($gift->customer_id && $gift->customer_id !== $s->customer_id)) {
                    $this->fail('Gift card is unavailable or has insufficient balance.');
                }
                $gift->decrement('balance', $amount);
            }
            $p = Payment::create(['gift_card_id' => $gift?->id, 'branch_id' => $s->branch_id, 'sale_id' => $s->id, 'method' => $method, 'amount' => $amount, 'reference' => $reference, 'idempotency_key' => $key, 'user_id' => auth()->id()]);
            $s->increment('paid', $amount);
            app(Ledger::class)->post($s->branch_id, 'payment:'.$p->id, 'Payment '.$s->number, [$method === 'gift_card' ? 'gift_liability' : ($method === 'cash' ? 'cash' : 'bank') => $amount, 'receivable' => -$amount]);
            Audit::record('payment.received', 'sale:'.$s->id, ['amount' => $amount, 'method' => $method]);

            return $p;
        });
    }

    public function refund(Sale $sale, string $reason): void
    {
        $quantities = $sale->fresh()->items->mapWithKeys(fn ($line) => [$line->id => $line->quantity - $line->returned_quantity])->filter()->all();
        $this->refundItems($sale, $quantities, $reason, (string) Str::uuid());
    }

    public function refundItems(Sale $sale, array $quantities, string $reason, string $key): CreditNote
    {
        Modules::require($sale->channel === 'online' ? 'ecommerce' : 'pos', $sale->branch_id);

        return DB::transaction(function () use ($sale, $quantities, $reason, $key) {
            $s = Sale::whereKey($sale->id)->lockForUpdate()->firstOrFail();
            if ($note = CreditNote::where('idempotency_key', $key)->first()) {
                if ($note->sale_id !== $s->id) {
                    $this->fail('Credit-note key belongs to another sale.');
                }

return $note;
            }
            if (in_array($s->status, ['refunded', 'cancelled']) || strlen(trim($reason)) < 3 || ! $quantities) {
                $this->fail('Select eligible quantities and provide a reason.');
            }
            Branch::whereKey($s->branch_id)->lockForUpdate()->firstOrFail();
            $revenueCredit = 0;
            $taxCredit = 0;
            $cost = 0;
            $prefix = 0;
            $allReturned = true;
            $selected = [];
            $items = $s->items()->orderBy('id')->lockForUpdate()->get();
            foreach (array_keys($quantities) as $id) {
                if (! $items->contains('id', (int) $id)) {
                    $this->fail('Return line does not belong to this sale.');
                }
            }
            foreach ($items as $line) {
                $qty = (int) ($quantities[$line->id] ?? 0);
                $old = $line->returned_quantity;
                if ($qty < 0 || $qty > $line->quantity - $old) {
                    $this->fail('Return exceeds unreturned quantity.');
                }
                $new = $old + $qty;
                if ($new < $line->quantity) {
                    $allReturned = false;
                }
                if ($qty) {
                    $share = fn ($pool) => $s->subtotal ? intdiv($pool * ($prefix + $line->total), $s->subtotal) - intdiv($pool * $prefix, $s->subtotal) : 0;
                    $revShare = $share($s->subtotal - $s->discount);
                    $taxShare = $share($s->tax);
                    $revenueCredit += intdiv($revShare * $new, $line->quantity) - intdiv($revShare * $old, $line->quantity);
                    $taxCredit += intdiv($taxShare * $new, $line->quantity) - intdiv($taxShare * $old, $line->quantity);
                    if ($line->product_id) {
                        app(Inventory::class)->move($s->branch_id, $line->product_id, $qty, 'customer return', 'sale:'.$s->id);
                        $cost += $qty * $line->unit_cost;
                    }
                    $commissionDelta = intdiv($line->commission * $new, $line->quantity) - $line->commission_reversed;
                    if ($line->employee_id && $commissionDelta > 0 && Payroll::where('employee_id', $line->employee_id)->where('month', $line->created_at->timezone('Asia/Dhaka')->format('Y-m'))->where('status', 'paid')->exists()) {
                        $month = now()->format('Y-m');
                        while (Payroll::where('employee_id', $line->employee_id)->where('month', $month)->exists()) {
                            $month = Carbon::parse($month.'-01')->addMonth()->format('Y-m');
                        }
                        $adjustment = CommissionAdjustment::firstOrCreate(['sale_id' => $s->id, 'employee_id' => $line->employee_id], ['branch_id' => $s->branch_id, 'amount' => 0, 'month' => $month]);
                        $adjustment->decrement('amount', $commissionDelta);
                    }
                    $line->update(['returned_quantity' => $new, 'commission_reversed' => $line->commission_reversed + $commissionDelta]);
                    $selected[$line->id] = $qty;
                }
                $prefix += $line->total;
            }
            if (! $selected) {
                $this->fail('Select at least one return quantity.');
            }
            $tipCredit = $allReturned ? $s->tip : 0;
            $credit = $revenueCredit + $taxCredit + $tipCredit;
            $note = CreditNote::create(['branch_id' => $s->branch_id, 'sale_id' => $s->id, 'idempotency_key' => $key, 'amount' => $credit, 'tax' => $taxCredit, 'reason' => $reason, 'items' => $selected]);
            app(Ledger::class)->post($s->branch_id, 'credit:'.$note->id, 'Credit note '.$s->number, ['receivable' => -$credit, 'revenue' => $revenueCredit, 'tax_payable' => $taxCredit, 'tips_payable' => $tipCredit]);
            if ($cost) {
                app(Ledger::class)->post($s->branch_id, 'credit:'.$note->id.':cost', 'Returned product cost', ['inventory' => $cost, 'cost_of_goods' => -$cost]);
            }
            $newCredited = $s->credited + $credit;
            $refund = max(0, ($s->paid - $s->refunded) - ($s->total - $newCredited));
            $remaining = $refund;
            foreach ($s->payments()->where('direction', 'in')->orderBy('id')->get() as $payment) {
                $returned = Payment::where('original_payment_id', $payment->id)->sum('amount');
                $amount = min($remaining, $payment->amount - $returned);
                if ($amount <= 0) {
                    continue;
                }
                if ($payment->gift_card_id) {
                    GiftCard::whereKey($payment->gift_card_id)->lockForUpdate()->firstOrFail()->increment('balance', $amount);
                }
                $out = Payment::create(['branch_id' => $s->branch_id, 'sale_id' => $s->id, 'method' => $payment->method, 'gift_card_id' => $payment->gift_card_id, 'original_payment_id' => $payment->id, 'direction' => 'out', 'amount' => $amount, 'reference' => $reason, 'idempotency_key' => 'credit:'.$note->id.':payment:'.$payment->id, 'user_id' => auth()->id()]);
                app(Ledger::class)->post($s->branch_id, 'payment:'.$out->id, 'Refund '.$s->number, [$payment->method === 'gift_card' ? 'gift_liability' : ($payment->method === 'cash' ? 'cash' : 'bank') => -$amount, 'receivable' => $amount]);
                $remaining -= $amount;
            }
            if ($remaining !== 0) {
                $this->fail('Refund allocation could not be reconciled.');
            }
            if ($s->customer_id) {
                $c = Customer::whereKey($s->customer_id)->lockForUpdate()->firstOrFail();
                $originalEarned = LoyaltyEntry::where('sale_id', $s->id)->where('reason', 'sale')->first();
                if ($originalEarned) {
                    $netPoints = $originalEarned->points;
                    $target = $allReturned ? -$netPoints : -intdiv($netPoints * $newCredited, max(1, $s->total));
                    $already = LoyaltyEntry::where('sale_id', $s->id)->where('reason', 'like', 'credit:%')->sum('points');
                    $delta = $target - $already;
                    $c->update(['loyalty_points' => max(0, $c->loyalty_points + $delta)]);
                    LoyaltyEntry::create(['branch_id' => $s->branch_id, 'customer_id' => $c->id, 'sale_id' => $s->id, 'points' => $delta, 'reason' => 'credit:'.$note->id]);
                }
            }
            if ($allReturned && $s->coupon_code) {
                Coupon::where('code', $s->coupon_code)->where('used', '>', 0)->decrement('used');
            }
            $s->update(['credited' => $newCredited, 'tax_credited' => $s->tax_credited + $taxCredit, 'refunded' => $s->refunded + $refund, 'status' => $allReturned ? ($s->paid ? 'refunded' : 'cancelled') : $s->status, 'commission' => $s->items()->sum('commission') - $s->items()->sum('commission_reversed'), 'notes' => $reason]);
            Audit::record('sale.credit_note', 'sale:'.$s->id, ['credit_note' => $note->id, 'credited' => $credit, 'refunded' => $refund, 'reason' => $reason]);

            return $note;
        });
    }

    public function fulfill(Sale $sale, string $status): void
    {
        Modules::require('ecommerce', $sale->branch_id);
        DB::transaction(function () use ($sale, $status) {
            $s = Sale::whereKey($sale->id)->lockForUpdate()->firstOrFail();
            $allowed = ['pending' => ['processing'], 'processing' => ['ready', 'dispatched'], 'ready' => ['collected'], 'dispatched' => ['delivered']];
            if (in_array($s->status, ['refunded', 'cancelled']) || ! in_array($status,$allowed[$s->fulfillment] ?? [])) {
                $this->fail('Invalid fulfillment transition.');
            }
            if (in_array($status,['collected', 'delivered']) && $s->paid - $s->refunded < $s->total - $s->credited) {
                $this->fail('Collect the outstanding balance before completing fulfillment.');
            }
            $s->update(['fulfillment' => $status, 'status' => in_array($status,['collected', 'delivered']) ? 'completed' : 'confirmed']);
            Audit::record('order.'.$status,'sale:'.$s->id);
        });
    }
}
