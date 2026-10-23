<?php

namespace App\Filament\Resources;

use App\Models\ServiceRecipe;

class ServiceRecipeResource extends TenantResource
{
    protected static ?string $model = ServiceRecipe::class;

    protected static ?string $navigationGroup = 'Inventory';

    protected static ?string $navigationIcon = 'heroicon-o-beaker';

    public static ?string $module = 'inventory';

    public static string $ability = 'inventory';

    public static bool $editable = true;

    public static bool $creatable = true;

    public static function getPages(): array
    {
        return ['index' => ServiceRecipeResource\Pages\Manage::route('/')];
    }
}
