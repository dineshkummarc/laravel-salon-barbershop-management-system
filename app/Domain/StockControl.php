<?php

namespace App\Domain;

use App\Models\Branch;
use App\Models\ServiceRecipe;
use App\Models\StockAdjustment;
use App\Support\Modules;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StockControl
{
    public function adjust(StockAdjustment $record): void
    {
        Modules::require('inventory', $record->branch_id);
        DB::transaction(function () use ($record) {
            $r = StockAdjustment::whereKey($record->id)->lockForUpdate()->firstOrFail();
            if ($r->status !== 'draft' || ! $r->quantity) {
                throw ValidationException::withMessages(['quantity' => 'Only non-zero draft adjustments can be posted.']);
            }
            Branch::whereKey($r->branch_id)->lockForUpdate()->firstOrFail();
            app(Inventory::class)->move($r->branch_id, $r->product_id, $r->quantity, $r->reason, 'adjustment:'.$r->id);
            $cost = $r->product->cost * $r->quantity;
            app(Ledger::class)->post($r->branch_id, 'adjustment:'.$r->id, 'Stock adjustment: '.$r->reason, ['inventory' => $cost, 'stock_variance' => -$cost]);
            $r->update(['status' => 'posted']);
            Audit::record('stock.adjusted', 'adjustment:'.$r->id, ['quantity' => $r->quantity]);
        });
    }

    public function consume(int $branch, int $service, int $quantity, string $reference): int
    {
        if (! Modules::enabled('inventory', $branch)) {
            return 0;
        }
        $cost = 0;
        foreach (ServiceRecipe::where('service_id', $service)->with('product')->get() as $recipe) {
            app(Inventory::class)->move($branch, $recipe->product_id, -$recipe->quantity * $quantity, 'service consumption', $reference);
            $cost += $recipe->product->cost * $recipe->quantity * $quantity;
        }

return $cost;
    }
}
