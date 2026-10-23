<?php

namespace App\Filament\Resources;

use App\Models\User;
use App\Support\BusinessContext;
use App\Support\ResourceSchema;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationGroup = 'Business';

    protected static ?string $navigationLabel = 'Team access';

    protected static ?string $navigationIcon = 'heroicon-o-key';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('business_id', app(BusinessContext::class)->id())->whereNot('role', 'platform');
    }

    public static function canViewAny(): bool
    {
        return (bool) auth()->user()?->allows('settings') && (bool) app(BusinessContext::class)->id();
    }

    public static function canCreate(): bool
    {
        return static::canViewAny();
    }

    public static function canEdit($record): bool
    {
        return static::canViewAny() && $record->id !== auth()->id();
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('name')->required()->maxLength(120), TextInput::make('email')->email()->required()->unique(ignoreRecord: true), TextInput::make('phone'),
            TextInput::make('password')->password()->minLength(10)->required(fn ($operation) => $operation === 'create')->dehydrated(fn ($state) => filled($state))->helperText('Leave blank to keep the current password.'),
            Select::make('role')->options(['owner' => 'Owner', 'admin' => 'Business administrator', 'manager' => 'Branch manager', 'reception' => 'Receptionist', 'cashier' => 'Cashier', 'stylist' => 'Stylist', 'inventory' => 'Inventory officer', 'accounts' => 'Accountant', 'hr' => 'HR/payroll'])->required(),
            ResourceSchema::branch()->required(false)->placeholder('All branches'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([Tables\Columns\TextColumn::make('name')->searchable(), Tables\Columns\TextColumn::make('email')->searchable(), Tables\Columns\TextColumn::make('role')->badge(), Tables\Columns\TextColumn::make('branch_id')])->actions([Tables\Actions\EditAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => UserResource\Pages\Manage::route('/')];
    }
}
