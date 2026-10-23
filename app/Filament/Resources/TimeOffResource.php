<?php

namespace App\Filament\Resources;

use App\Models\TimeOff;

class TimeOffResource extends TenantResource
{
    protected static ?string $model = TimeOff::class;

    protected static ?string $navigationGroup = 'Team';

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    public static ?string $module = 'hr';

    public static string $ability = 'hr';

    public static bool $editable = false;

    public static bool $creatable = true;

    public static function getPages(): array
    {
        return ['index' => TimeOffResource\Pages\Manage::route('/')];
    }
}
