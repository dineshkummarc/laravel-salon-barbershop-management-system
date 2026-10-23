<?php

namespace App\Filament\Resources\CustomerResource\Pages;

use App\Filament\Resources\CustomerResource;
use App\Filament\Resources\ManageTenantRecords;

class Manage extends ManageTenantRecords
{
    protected static string $resource = CustomerResource::class;
}
