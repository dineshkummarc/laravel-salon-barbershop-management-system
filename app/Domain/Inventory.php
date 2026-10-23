<?php

namespace App\Domain;

use App\Models\Branch;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\Transfer;
use App\Support\Modules;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class Inventory
{
    public function move(int $branch, int $product, int $quantity, string $reason, string $reference): void
    {
        Branch::findOrFail($branch);
        Product::findOrFail($product);
        // Caller holds a branch lock for new stock rows; unique key provides a second guard.
        $stock = Stock::firstOrCreate(['branch_id' => $branch, 'product_id' => $product], ['quantity' => 0]);
        $stock = Stock::whereKey($stock->id)->lockForUpdate()->firstOrFail();
        if ($stock->quantity + $quantity < 0) {
            throw ValidationException::withMessages(['stock' => 'Insufficient stock for '.Product::find($product)->name.'.']);
        }
        $stock->increment('quantity', $quantity);
        StockMovement::create(['branch_id' => $branch, 'product_id' => $product, 'quantity' => $quantity, 'reason' => $reason, 'reference' => $reference, 'user_id' => auth()->id()]);
    }

    public function receive(Purchase $purchase, int $quantity): void
    {
        Modules::require('purchasing', $purchase->branch_id);
        DB::transaction(function () use ($purchase, $quantity) {
            $p = Purchase::whereKey($purchase->id)->lockForUpdate()->firstOrFail();
            Branch::whereKey($p->branch_id)->lockForUpdate()->firstOrFail();
            if ($quantity < 1 || $quantity > $p->quantity - $p->received) {
                throw ValidationException::withMessages(['quantity' => 'Quantity exceeds remaining purchase quantity.']);
            }
            $this->move($p->branch_id, $p->product_id, $quantity, 'purchase', 'purchase:'.$p->id);
            app(Ledger::class)->post($p->branch_id, 'purchase:'.$p->id.':'.($p->received + $quantity), 'Goods received', ['inventory' => $quantity * $p->product->cost, 'purchase_price_variance' => $quantity * ($p->unit_cost - $p->product->cost), 'payable' => -$quantity * $p->unit_cost]);
            $p->received += $quantity;
            $p->status = $p->received === $p->quantity ? 'received' : 'partially_received';
            $p->save();
            Audit::record('purchase.received', 'purchase:'.$p->id, ['quantity' => $quantity]);
        });
    }

    public function dispatch(Transfer $transfer): void
    {
        Modules::require('inventory', $transfer->branch_id);
        DB::transaction(function () use ($transfer) {
            $t = Transfer::whereKey($transfer->id)->lockForUpdate()->firstOrFail();
            if ($t->status !== 'requested') {
                throw ValidationException::withMessages(['status' => 'Only requested transfers can be dispatched.']);
            }
            Branch::whereKey($t->branch_id)->lockForUpdate()->firstOrFail();
            Branch::findOrFail($t->destination_id);
            if ($t->branch_id === $t->destination_id) {
                throw ValidationException::withMessages(['destination_id' => 'Choose another branch.']);
            }
            $this->move($t->branch_id, $t->product_id, -$t->quantity, 'transfer dispatch', 'transfer:'.$t->id);
            $cost = $t->quantity * $t->product->cost;
            app(Ledger::class)->post($t->branch_id, 'transfer:'.$t->id.':dispatch', 'Stock in transit', ['in_transit' => $cost, 'inventory' => -$cost]);
            $t->update(['status' => 'dispatched']);
            Audit::record('transfer.dispatched', 'transfer:'.$t->id);
        });
    }

    public function receiveTransfer(Transfer $transfer): void
    {
        Modules::require('inventory', $transfer->branch_id);
        DB::transaction(function () use ($transfer) {
            $t = Transfer::whereKey($transfer->id)->lockForUpdate()->firstOrFail();
            if ($t->status !== 'dispatched') {
                throw ValidationException::withMessages(['status' => 'Transfer is not in transit.']);
            }
            Branch::whereKey($t->destination_id)->lockForUpdate()->firstOrFail();
            $this->move($t->destination_id, $t->product_id, $t->quantity, 'transfer receipt', 'transfer:'.$t->id);
            $cost = $t->quantity * $t->product->cost;
            app(Ledger::class)->post($t->branch_id, 'transfer:'.$t->id.':clear', 'Clear transit', ['in_transit' => -$cost, 'branch_clearing' => $cost]);
            app(Ledger::class)->post($t->destination_id, 'transfer:'.$t->id.':receive', 'Transfer received', ['inventory' => $cost, 'branch_clearing' => -$cost]);
            $t->update(['status' => 'received']);
            Audit::record('transfer.received','transfer:'.$t->id);
        });
    }
}
