<?php

namespace App\Filament\Resources;

use App\Models\Content;

class ContentResource extends TenantResource
{
    protected static ?string $model = Content::class;

    protected static ?string $navigationGroup = 'Business';

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    public static ?string $module = 'content';

    public static string $ability = 'settings';

    public static bool $editable = true;

    public static bool $creatable = true;

    public static function getPages(): array
    {
        return ['index' => ContentResource\Pages\Manage::route('/')];
    }
}
