<?php

namespace App\Filament\Resources;

use App\Models\StockAdjustment;

class StockAdjustmentResource extends TenantResource
{
    protected static ?string $model = StockAdjustment::class;

    protected static ?string $navigationGroup = 'Inventory';

    protected static ?string $navigationIcon = 'heroicon-o-scale';

    public static ?string $module = 'inventory';

    public static string $ability = 'inventory';

    public static bool $editable = false;

    public static bool $creatable = true;

    public static function getPages(): array
    {
        return ['index' => StockAdjustmentResource\Pages\Manage::route('/')];
    }
}
