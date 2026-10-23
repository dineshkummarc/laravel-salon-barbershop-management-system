<?php

namespace App\Filament\Widgets;

use App\Models\Booking;
use App\Models\Customer;
use App\Models\Sale;
use App\Support\Money;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class BusinessStats extends StatsOverviewWidget
{
    protected static ?string $pollingInterval = '60s';

    public static function canView(): bool
    {
        return (bool) auth()->user()?->allows('reports');
    }

    protected function getStats(): array
    {
        $start = now('Asia/Dhaka')->startOfDay()->utc();

        return [Stat::make('Today’s sales', Money::format(Sale::where('created_at', '>=', $start)->whereNotIn('status', ['cancelled', 'refunded'])->sum(DB::raw('total-credited'))))->description('Across your selected branches')->icon('heroicon-o-banknotes')->color('success'),
            Stat::make('Today’s appointments', Booking::where('starts_at', '>=', $start)->where('starts_at', '<', $start->copy()->addDay())->count())->description('Online and walk-in')->icon('heroicon-o-calendar-days'),
            Stat::make('Pending online orders', Sale::where('channel', 'online')->whereNotIn('status', ['completed', 'cancelled', 'refunded'])->count())->description('Ready for your attention')->color('warning'),
            Stat::make('Customers', Customer::count())->description('People at the heart of your business')->icon('heroicon-o-users')];
    }
}
