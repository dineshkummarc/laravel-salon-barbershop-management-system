<?php

namespace App\Filament\Resources\AttendanceResource\Pages;

use App\Filament\Resources\AttendanceResource;
use App\Filament\Resources\ManageTenantRecords;

class Manage extends ManageTenantRecords
{
    protected static string $resource = AttendanceResource::class;
}
