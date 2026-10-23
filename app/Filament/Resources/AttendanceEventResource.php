<?php

namespace App\Filament\Resources;

use App\Models\AttendanceEvent;

class AttendanceEventResource extends TenantResource
{
    protected static ?string $model = AttendanceEvent::class;

    protected static ?string $navigationGroup = 'Team';

    protected static ?string $navigationIcon = 'heroicon-o-finger-print';

    public static ?string $module = 'attendance';

    public static string $ability = 'settings';

    public static bool $editable = false;

    public static bool $creatable = false;

    public static function getPages(): array
    {
        return ['index' => AttendanceEventResource\Pages\Manage::route('/')];
    }
}
