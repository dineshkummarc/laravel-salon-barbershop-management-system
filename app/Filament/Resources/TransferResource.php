<?php

namespace App\Filament\Resources;

use App\Models\Transfer;

class TransferResource extends TenantResource
{
    protected static ?string $model = Transfer::class;

    protected static ?string $navigationGroup = 'Inventory';

    protected static ?string $navigationIcon = 'heroicon-o-arrow-path';

    public static ?string $module = 'inventory';

    public static string $ability = 'inventory';

    public static bool $editable = false;

    public static bool $creatable = true;

    public static function getPages(): array
    {
        return ['index' => TransferResource\Pages\Manage::route('/')];
    }
}
