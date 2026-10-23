<?php

namespace App\Filament\Resources;

use App\Models\Sale;

class SaleResource extends TenantResource
{
    protected static ?string $model = Sale::class;

    protected static ?string $navigationGroup = 'Operations';

    protected static ?string $navigationIcon = 'heroicon-o-receipt-percent';

    public static ?string $module = null;

    public static string $ability = 'orders';

    public static bool $editable = false;

    public static bool $creatable = false;

    public static function getPages(): array
    {
        return ['index' => SaleResource\Pages\Manage::route('/')];
    }
}
