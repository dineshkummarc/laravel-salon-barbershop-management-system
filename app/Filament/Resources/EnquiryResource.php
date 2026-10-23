<?php

namespace App\Filament\Resources;

use App\Models\Enquiry;

class EnquiryResource extends TenantResource
{
    protected static ?string $model = Enquiry::class;

    protected static ?string $navigationGroup = 'Customers';

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    public static ?string $module = 'website';

    public static string $ability = 'crm';

    public static bool $editable = true;

    public static bool $creatable = false;

    public static function getPages(): array
    {
        return ['index' => EnquiryResource\Pages\Manage::route('/')];
    }
}
