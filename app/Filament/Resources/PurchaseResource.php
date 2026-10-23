<?php

namespace App\Filament\Resources;

use App\Models\Purchase;

class PurchaseResource extends TenantResource
{
    protected static ?string $model = Purchase::class;

    protected static ?string $navigationGroup = 'Inventory';

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    public static ?string $module = 'purchasing';

    public static string $ability = 'purchasing';

    public static bool $editable = false;

    public static bool $creatable = true;

    public static function getPages(): array
    {
        return ['index' => PurchaseResource\Pages\Manage::route('/')];
    }
}
