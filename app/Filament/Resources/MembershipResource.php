<?php

namespace App\Filament\Resources;

use App\Models\Membership;

class MembershipResource extends TenantResource
{
    protected static ?string $model = Membership::class;

    protected static ?string $navigationGroup = 'Customers';

    protected static ?string $navigationIcon = 'heroicon-o-identification';

    public static ?string $module = 'memberships';

    public static string $ability = 'packages';

    public static bool $editable = false;

    public static bool $creatable = true;

    public static function getPages(): array
    {
        return ['index' => MembershipResource\Pages\Manage::route('/')];
    }
}
