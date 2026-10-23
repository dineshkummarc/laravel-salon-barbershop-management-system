<?php

namespace App\Filament\Resources;

use App\Models\MembershipPlan;

class MembershipPlanResource extends TenantResource
{
    protected static ?string $model = MembershipPlan::class;

    protected static ?string $navigationGroup = 'Customers';

    protected static ?string $navigationIcon = 'heroicon-o-identification';

    public static ?string $module = 'memberships';

    public static string $ability = 'packages';

    public static bool $editable = true;

    public static bool $creatable = true;

    public static function getPages(): array
    {
        return ['index' => MembershipPlanResource\Pages\Manage::route('/')];
    }
}
