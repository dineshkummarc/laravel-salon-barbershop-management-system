<?php

namespace App\Filament\Resources;

use App\Models\CreditNote;

class CreditNoteResource extends TenantResource
{
    protected static ?string $model = CreditNote::class;

    protected static ?string $navigationGroup = 'Finance';

    protected static ?string $navigationIcon = 'heroicon-o-book-open';

    public static ?string $module = 'accounting';

    public static string $ability = 'accounting';

    public static bool $editable = false;

    public static bool $creatable = false;

    public static function getPages(): array
    {
        return ['index' => CreditNoteResource\Pages\Manage::route('/')];
    }
}
