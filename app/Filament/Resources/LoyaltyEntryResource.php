<?php

namespace App\Filament\Resources;

use App\Models\LoyaltyEntry;

class LoyaltyEntryResource extends TenantResource
{
    protected static ?string $model = LoyaltyEntry::class;

    protected static ?string $navigationGroup = 'Customers';

    protected static ?string $navigationIcon = 'heroicon-o-star';

    public static ?string $module = 'loyalty';

    public static string $ability = 'crm';

    public static bool $editable = false;

    public static bool $creatable = false;

    public static function getPages(): array
    {
        return ['index' => LoyaltyEntryResource\Pages\Manage::route('/')];
    }
}
