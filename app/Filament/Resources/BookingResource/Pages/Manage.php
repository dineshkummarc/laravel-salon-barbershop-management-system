<?php

namespace App\Filament\Resources\BookingResource\Pages;

use App\Filament\Resources\BookingResource;
use App\Filament\Resources\ManageTenantRecords;

class Manage extends ManageTenantRecords
{
    protected static string $resource = BookingResource::class;
}
