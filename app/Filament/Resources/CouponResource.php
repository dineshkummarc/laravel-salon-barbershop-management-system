<?php

namespace App\Filament\Resources;

use App\Models\Coupon;

class CouponResource extends TenantResource
{
    protected static ?string $model = Coupon::class;

    protected static ?string $navigationGroup = 'Customers';

    protected static ?string $navigationIcon = 'heroicon-o-tag';

    public static ?string $module = 'marketing';

    public static string $ability = 'marketing';

    public static bool $editable = true;

    public static bool $creatable = true;

    public static function getPages(): array
    {
        return ['index' => CouponResource\Pages\Manage::route('/')];
    }
}
