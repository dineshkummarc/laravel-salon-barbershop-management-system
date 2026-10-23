<?php

namespace App\Filament\Resources;

use App\Models\Campaign;

class CampaignResource extends TenantResource
{
    protected static ?string $model = Campaign::class;

    protected static ?string $navigationGroup = 'Customers';

    protected static ?string $navigationIcon = 'heroicon-o-megaphone';

    public static ?string $module = 'marketing';

    public static string $ability = 'marketing';

    public static bool $editable = false;

    public static bool $creatable = true;

    public static function getPages(): array
    {
        return ['index' => CampaignResource\Pages\Manage::route('/')];
    }
}
