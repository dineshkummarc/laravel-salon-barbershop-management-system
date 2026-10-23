<?php

namespace App\Filament\Resources;

use App\Models\Customer;

class CustomerResource extends TenantResource
{
    protected static ?string $model = Customer::class;

    protected static ?string $navigationGroup = 'Customers';

    protected static ?string $navigationIcon = 'heroicon-o-users';

    public static ?string $module = 'crm';

    public static string $ability = 'crm';

    public static bool $editable = true;

    public static bool $creatable = true;

    public static function getPages(): array
    {
        return ['index' => CustomerResource\Pages\Manage::route('/')];
    }
}
