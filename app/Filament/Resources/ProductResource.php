<?php

namespace App\Filament\Resources;

use App\Models\Product;

class ProductResource extends TenantResource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationGroup = 'Catalog';

    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';

    public static ?string $module = 'products';

    public static string $ability = 'catalog';

    public static bool $editable = true;

    public static bool $creatable = true;

    public static function getPages(): array
    {
        return ['index' => ProductResource\Pages\Manage::route('/')];
    }
}
