<?php

namespace App\Filament\Resources;

use App\Models\CashSession;

class CashSessionResource extends TenantResource
{
    protected static ?string $model = CashSession::class;

    protected static ?string $navigationGroup = 'Finance';

    protected static ?string $navigationIcon = 'heroicon-o-calculator';

    public static ?string $module = 'pos';

    public static string $ability = 'cash';

    public static bool $editable = false;

    public static bool $creatable = true;

    public static function getPages(): array
    {
        return ['index' => CashSessionResource\Pages\Manage::route('/')];
    }
}
