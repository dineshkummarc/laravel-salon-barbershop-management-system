<?php

namespace App\Filament\Resources;

use App\Models\MessageDelivery;

class MessageDeliveryResource extends TenantResource
{
    protected static ?string $model = MessageDelivery::class;

    protected static ?string $navigationGroup = 'Customers';

    protected static ?string $navigationIcon = 'heroicon-o-envelope';

    public static ?string $module = 'marketing';

    public static string $ability = 'marketing';

    public static bool $editable = false;

    public static bool $creatable = false;

    public static function getPages(): array
    {
        return ['index' => MessageDeliveryResource\Pages\Manage::route('/')];
    }
}
