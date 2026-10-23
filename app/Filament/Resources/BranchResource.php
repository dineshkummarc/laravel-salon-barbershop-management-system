<?php

namespace App\Filament\Resources;

use App\Models\Branch;

class BranchResource extends TenantResource
{
    protected static ?string $model = Branch::class;

    protected static ?string $navigationGroup = 'Business';

    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';

    public static ?string $module = null;

    public static string $ability = 'settings';

    public static bool $editable = true;

    public static bool $creatable = true;

    public static function getPages(): array
    {
        return ['index' => BranchResource\Pages\Manage::route('/')];
    }
}
