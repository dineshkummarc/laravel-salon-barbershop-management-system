<?php

namespace App\Filament\Resources;

use App\Models\CommissionAdjustment;

class CommissionAdjustmentResource extends TenantResource
{
    protected static ?string $model = CommissionAdjustment::class;

    protected static ?string $navigationGroup = 'Team';

    protected static ?string $navigationIcon = 'heroicon-o-arrow-uturn-left';

    public static ?string $module = 'payroll';

    public static string $ability = 'payroll';

    public static bool $editable = false;

    public static bool $creatable = false;

    public static function getPages(): array
    {
        return ['index' => CommissionAdjustmentResource\Pages\Manage::route('/')];
    }
}
