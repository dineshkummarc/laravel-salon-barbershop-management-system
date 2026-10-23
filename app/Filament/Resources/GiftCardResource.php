<?php

namespace App\Filament\Resources;

use App\Models\GiftCard;

class GiftCardResource extends TenantResource
{
    protected static ?string $model = GiftCard::class;

    protected static ?string $navigationGroup = 'Customers';

    protected static ?string $navigationIcon = 'heroicon-o-gift';

    public static ?string $module = 'gift_cards';

    public static string $ability = 'packages';

    public static bool $editable = false;

    public static bool $creatable = true;

    public static function getPages(): array
    {
        return ['index' => GiftCardResource\Pages\Manage::route('/')];
    }
}
