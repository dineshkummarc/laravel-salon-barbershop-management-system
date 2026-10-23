<?php

namespace App\Filament\Widgets;

use App\Models\Sale;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class SalesChart extends ChartWidget
{
    protected static ?string $heading = 'Sales · last 7 days (BDT)';

    public static function canView(): bool
    {
        return (bool) auth()->user()?->allows('reports');
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $labels = [];
        $data = [];
        foreach (range(6, 0) as $i) {
            $day = now('Asia/Dhaka')->subDays($i);
            $labels[] = $day->format('D');
            $data[] = Sale::whereBetween('created_at', [$day->copy()->startOfDay()->utc(), $day->copy()->endOfDay()->utc()])->whereNotIn('status', ['cancelled', 'refunded'])->sum(DB::raw('total-credited')) / 100;
        }

        return ['datasets' => [['label' => 'Sales', 'data' => $data, 'backgroundColor' => '#0d766e', 'borderRadius' => 6]], 'labels' => $labels];
    }
}
