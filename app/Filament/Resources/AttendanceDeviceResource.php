<?php

namespace App\Filament\Resources;

use App\Models\AttendanceDevice;

class AttendanceDeviceResource extends TenantResource
{
    protected static ?string $model = AttendanceDevice::class;

    protected static ?string $navigationGroup = 'Team';

    protected static ?string $navigationIcon = 'heroicon-o-finger-print';

    public static ?string $module = 'attendance';

    public static string $ability = 'settings';

    public static bool $editable = true;

    public static bool $creatable = true;

    public static function getPages(): array
    {
        return ['index' => AttendanceDeviceResource\Pages\Manage::route('/')];
    }
}
