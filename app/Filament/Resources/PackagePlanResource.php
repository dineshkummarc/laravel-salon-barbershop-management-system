<?php

namespace App\Filament\Resources;

use App\Models\PackagePlan;

class PackagePlanResource extends TenantResource
{
    protected static ?string $model = PackagePlan::class;

    protected static ?string $navigationGroup = 'Customers';

    protected static ?string $navigationIcon = 'heroicon-o-ticket';

    public static ?string $module = 'packages';

    public static string $ability = 'packages';

    public static bool $editable = true;

    public static bool $creatable = true;

    public static function getPages(): array
    {
        return ['index' => PackagePlanResource\Pages\Manage::route('/')];
    }
}
