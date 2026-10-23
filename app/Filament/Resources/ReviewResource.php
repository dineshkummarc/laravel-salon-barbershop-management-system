<?php

namespace App\Filament\Resources;

use App\Models\Review;

class ReviewResource extends TenantResource
{
    protected static ?string $model = Review::class;

    protected static ?string $navigationGroup = 'Customers';

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-ellipsis';

    public static ?string $module = 'reviews';

    public static string $ability = 'crm';

    public static bool $editable = false;

    public static bool $creatable = false;

    public static function getPages(): array
    {
        return ['index' => ReviewResource\Pages\Manage::route('/')];
    }
}
