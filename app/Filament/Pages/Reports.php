<?php

namespace App\Filament\Pages;

use App\Models\Booking;
use App\Models\LedgerEntry;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Stock;
use App\Support\Modules;
use Carbon\Carbon;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;

class Reports extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationGroup = 'Finance';

    protected static string $view = 'filament.pages.reports';

    public string $from;

    public string $to;

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->allows('reports') && Modules::enabled('reports');
    }

    public function mount(): void
    {
        $this->from = now('Asia/Dhaka')->startOfMonth()->toDateString();
        $this->to = now('Asia/Dhaka')->toDateString();
    }

    public function data(): array
    {
        $this->validate(['from' => 'required|date', 'to' => 'required|date|after_or_equal:from']);
        $start = Carbon::parse($this->from, 'Asia/Dhaka')->startOfDay()->utc();
        $end = Carbon::parse($this->to, 'Asia/Dhaka')->endOfDay()->utc();
        $entries = LedgerEntry::whereBetween('created_at', [$start, $end])->selectRaw('account, SUM(debit) as debit, SUM(credit) as credit')->groupBy('account')->get();
        $balance = LedgerEntry::where('created_at', '<=', $end)->selectRaw('account, SUM(debit) as debit, SUM(credit) as credit')->groupBy('account')->get();
        $accountNet = fn ($name) => ($entries->firstWhere('account', $name)?->credit ?? 0) - ($entries->firstWhere('account', $name)?->debit ?? 0);
        $revenue = $accountNet('revenue') + $accountNet('membership_revenue');
        $cost = -$accountNet('cost_of_goods') - $accountNet('expenses') - $accountNet('payroll_expense') - $accountNet('purchase_price_variance') - $accountNet('stock_variance');

        return ['revenue' => $revenue, 'cost' => $cost, 'profit' => $revenue - $cost, 'staff' => SaleItem::whereBetween('created_at', [$start, $end])->whereNotNull('employee_id')->selectRaw('employee_id, SUM(total) as gross, SUM(commission-commission_reversed) as commission')->groupBy('employee_id')->with('employee')->get(), 'entries' => $entries, 'balance' => $balance, 'sales' => Sale::whereBetween('created_at', [$start, $end])->whereNotIn('status', ['cancelled', 'refunded'])->sum(DB::raw('total-credited')), 'bookings' => Booking::whereBetween('starts_at', [$start, $end])->count(), 'lowStock' => Stock::with('product', 'branch')->where('quantity', '<=', 5)->get(), 'branches' => Sale::whereBetween('created_at', [$start, $end])->whereNotIn('status', ['cancelled', 'refunded'])->selectRaw('branch_id,SUM(total-credited) as total')->groupBy('branch_id')->with('branch')->get()];
    }
}
