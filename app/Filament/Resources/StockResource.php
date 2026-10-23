<?php

namespace App\Filament\Resources;

use App\Models\Stock;

class StockResource extends TenantResource
{
    protected static ?string $model = Stock::class;

    protected static ?string $navigationGroup = 'Inventory';

    protected static ?string $navigationIcon = 'heroicon-o-cube';

    public static ?string $module = 'inventory';

    public static string $ability = 'inventory';

    public static bool $editable = false;

    public static bool $creatable = false;

    public static function getPages(): array
    {
        return ['index' => StockResource\Pages\Manage::route('/')];
    }
}
