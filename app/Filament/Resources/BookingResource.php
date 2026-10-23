<?php

namespace App\Filament\Resources;

use App\Models\Booking;

class BookingResource extends TenantResource
{
    protected static ?string $model = Booking::class;

    protected static ?string $navigationGroup = 'Operations';

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    public static ?string $module = 'appointments';

    public static string $ability = 'appointments';

    public static bool $editable = false;

    public static bool $creatable = true;

    public static function getPages(): array
    {
        return ['index' => BookingResource\Pages\Manage::route('/')];
    }
}
