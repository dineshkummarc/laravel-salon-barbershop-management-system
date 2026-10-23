<?php

namespace App\Filament\Resources;

use App\Models\Supplier;

class SupplierResource extends TenantResource
{
    protected static ?string $model = Supplier::class;

    protected static ?string $navigationGroup = 'Inventory';

    protected static ?string $navigationIcon = 'heroicon-o-truck';

    public static ?string $module = 'purchasing';

    public static string $ability = 'purchasing';

    public static bool $editable = true;

    public static bool $creatable = true;

    public static function getPages(): array
    {
        return ['index' => SupplierResource\Pages\Manage::route('/')];
    }
}
