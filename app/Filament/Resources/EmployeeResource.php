<?php

namespace App\Filament\Resources;

use App\Models\Employee;

class EmployeeResource extends TenantResource
{
    protected static ?string $model = Employee::class;

    protected static ?string $navigationGroup = 'Team';

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    public static ?string $module = 'hr';

    public static string $ability = 'hr';

    public static bool $editable = true;

    public static bool $creatable = true;

    public static function getPages(): array
    {
        return ['index' => EmployeeResource\Pages\Manage::route('/')];
    }
}
