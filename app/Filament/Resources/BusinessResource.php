<?php

namespace App\Filament\Resources;

use App\Models\Business;
use App\Support\Modules;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class BusinessResource extends Resource
{
    protected static ?string $model = Business::class;

    protected static ?string $navigationGroup = 'Platform';

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';

    public static function canViewAny(): bool
    {
        return auth()->user()?->role === 'platform';
    }

    public static function canCreate(): bool
    {
        return static::canViewAny();
    }

    public static function canEdit($record): bool
    {
        return static::canViewAny();
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            DatePicker::make('subscription_ends_at')->default(today()->addDays(14))->helperText('Manual billing period. Seven-day grace by default.'), TextInput::make('name')->required(), TextInput::make('name_bn'), TextInput::make('slug')->required()->unique(ignoreRecord: true), TextInput::make('phone')->required(), TextInput::make('email')->email(), Textarea::make('description'),
            Select::make('status')->options(['active' => 'Active', 'suspended' => 'Suspended'])->required()->default('active'), Select::make('plan')->options(['basic' => 'Basic', 'professional' => 'Professional', 'enterprise' => 'Enterprise'])->default('professional')->required(),
            Select::make('modules')->multiple()->options(Modules::ALL)->default(array_keys(Modules::ALL))->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([Tables\Columns\TextColumn::make('name')->searchable(), Tables\Columns\TextColumn::make('slug'), Tables\Columns\TextColumn::make('plan')->badge(), Tables\Columns\TextColumn::make('status')->badge()])->actions([Tables\Actions\EditAction::make()->mutateFormDataUsing(function ($data) {
            Modules::validate($data['modules'], $data['plan'] ?? 'professional');

            return $data;
        })]);
    }

    public static function getPages(): array
    {
        return ['index' => BusinessResource\Pages\ManageBusinesses::route('/')];
    }
}
