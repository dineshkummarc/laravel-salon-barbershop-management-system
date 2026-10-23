<?php

namespace App\Filament\Resources;

use App\Models\AuditLog;

class AuditLogResource extends TenantResource
{
    protected static ?string $model = AuditLog::class;

    protected static ?string $navigationGroup = 'Business';

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    public static ?string $module = null;

    public static string $ability = 'settings';

    public static bool $editable = false;

    public static bool $creatable = false;

    public static function getPages(): array
    {
        return ['index' => AuditLogResource\Pages\Manage::route('/')];
    }
}
