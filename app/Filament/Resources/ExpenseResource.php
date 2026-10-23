<?php

namespace App\Filament\Resources;

use App\Models\Expense;

class ExpenseResource extends TenantResource
{
    protected static ?string $model = Expense::class;

    protected static ?string $navigationGroup = 'Finance';

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    public static ?string $module = 'expenses';

    public static string $ability = 'expenses';

    public static bool $editable = false;

    public static bool $creatable = true;

    public static function getPages(): array
    {
        return ['index' => ExpenseResource\Pages\Manage::route('/')];
    }
}
