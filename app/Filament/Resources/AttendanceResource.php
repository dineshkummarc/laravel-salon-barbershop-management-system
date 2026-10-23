<?php

namespace App\Filament\Resources;

use App\Models\Attendance;

class AttendanceResource extends TenantResource
{
    protected static ?string $model = Attendance::class;

    protected static ?string $navigationGroup = 'Team';

    protected static ?string $navigationIcon = 'heroicon-o-finger-print';

    public static ?string $module = 'attendance';

    public static string $ability = 'attendance';

    public static bool $editable = false;

    public static bool $creatable = true;

    public static function getPages(): array
    {
        return ['index' => AttendanceResource\Pages\Manage::route('/')];
    }
}
