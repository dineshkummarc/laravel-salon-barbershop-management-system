<?php

namespace App\Filament\Widgets;

use App\Models\Booking;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class UpcomingBookings extends TableWidget
{
    protected static ?string $heading = 'Your next appointments';

    public static function canView(): bool
    {
        return (bool) auth()->user()?->allows('appointments');
    }

    public function table(Table $table): Table
    {
        return $table->query(Booking::with('customer', 'service', 'employee')->where('starts_at', '>=', now()->startOfDay())->whereNotIn('status', ['cancelled', 'completed', 'no_show'])->orderBy('starts_at'))->columns([Tables\Columns\TextColumn::make('customer.name'), Tables\Columns\TextColumn::make('service.name'), Tables\Columns\TextColumn::make('starts_at')->dateTime('d M, H:i', 'Asia/Dhaka'), Tables\Columns\TextColumn::make('status')->badge()])->paginated([5]);
    }
}
