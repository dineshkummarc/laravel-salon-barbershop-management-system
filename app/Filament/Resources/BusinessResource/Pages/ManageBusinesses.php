<?php

namespace App\Filament\Resources\BusinessResource\Pages;

use App\Filament\Resources\BusinessResource;
use App\Support\Modules;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageBusinesses extends ManageRecords
{
    protected static string $resource = BusinessResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()->mutateFormDataUsing(function ($data) {
            Modules::validate($data['modules'], $data['plan'] ?? 'professional');

            return $data;
        })];
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        Modules::validate($data['modules'], $data['plan'] ?? 'professional');

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        Modules::validate($data['modules'], $data['plan'] ?? 'professional');

        return $data;
    }
}
