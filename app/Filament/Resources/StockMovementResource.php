<?php

namespace App\Filament\Resources;

use App\Models\StockMovement;

class StockMovementResource extends TenantResource
{
    protected static ?string $model = StockMovement::class;

    protected static ?string $navigationGroup = 'Inventory';

    protected static ?string $navigationIcon = 'heroicon-o-arrows-right-left';

    public static ?string $module = 'inventory';

    public static string $ability = 'inventory';

    public static bool $editable = false;

    public static bool $creatable = false;

    public static function getPages(): array
    {
        return ['index' => StockMovementResource\Pages\Manage::route('/')];
    }
}
