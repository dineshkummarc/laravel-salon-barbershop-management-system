<?php

namespace App\Filament\Resources\CustomerPackageResource\Pages;

use App\Filament\Resources\CustomerPackageResource;
use App\Filament\Resources\ManageTenantRecords;

class Manage extends ManageTenantRecords
{
    protected static string $resource = CustomerPackageResource::class;
}
