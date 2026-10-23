<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Support\BusinessContext;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class Manage extends ManageRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()->mutateFormDataUsing(fn ($data) => $data + ['business_id' => app(BusinessContext::class)->id()])];
    }
}
