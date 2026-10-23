<?php

namespace App\Filament\Resources;

use App\Models\Payroll;

class PayrollResource extends TenantResource
{
    protected static ?string $model = Payroll::class;

    protected static ?string $navigationGroup = 'Team';

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';

    public static ?string $module = 'payroll';

    public static string $ability = 'payroll';

    public static bool $editable = false;

    public static bool $creatable = true;

    public static function getPages(): array
    {
        return ['index' => PayrollResource\Pages\Manage::route('/')];
    }
}
