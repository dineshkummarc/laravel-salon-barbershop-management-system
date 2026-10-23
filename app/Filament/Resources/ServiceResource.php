<?php

namespace App\Filament\Resources;

use App\Models\Service;

class ServiceResource extends TenantResource
{
    protected static ?string $model = Service::class;

    protected static ?string $navigationGroup = 'Catalog';

    protected static ?string $navigationIcon = 'heroicon-o-scissors';

    public static ?string $module = 'services';

    public static string $ability = 'catalog';

    public static bool $editable = true;

    public static bool $creatable = true;

    public static function getPages(): array
    {
        return ['index' => ServiceResource\Pages\Manage::route('/')];
    }
}
