<?php

namespace App\Filament\Resources\AuditLogResource\Pages;

use App\Filament\Resources\AuditLogResource;
use App\Filament\Resources\ManageTenantRecords;

class Manage extends ManageTenantRecords
{
    protected static string $resource = AuditLogResource::class;
}
