<?php

namespace App\Filament\Resources;

use App\Models\CustomerPackage;

class CustomerPackageResource extends TenantResource
{
    protected static ?string $model = CustomerPackage::class;

    protected static ?string $navigationGroup = 'Customers';

    protected static ?string $navigationIcon = 'heroicon-o-gift';

    public static ?string $module = 'packages';

    public static string $ability = 'packages';

    public static bool $editable = false;

    public static bool $creatable = true;

    public static function getPages(): array
    {
        return ['index' => CustomerPackageResource\Pages\Manage::route('/')];
    }
}
