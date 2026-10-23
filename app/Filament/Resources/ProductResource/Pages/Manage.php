<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ManageTenantRecords;
use App\Filament\Resources\ProductResource;

class Manage extends ManageTenantRecords
{
    protected static string $resource = ProductResource::class;
}
