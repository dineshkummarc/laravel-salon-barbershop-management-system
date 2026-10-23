<?php

namespace App\Filament\Resources;

use App\Domain\Audit;
use App\Models\Business;
use App\Models\PlatformInvoice;
use App\Support\BusinessContext;
use App\Support\Modules;
use App\Support\Money;
use App\Support\ResourceSchema;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PlatformInvoiceResource extends TenantResource
{
    protected static ?string $model = PlatformInvoice::class;

    protected static ?string $navigationGroup = 'Business';

    protected static ?string $navigationLabel = 'Subscription invoices';

    protected static ?string $navigationIcon = 'heroicon-o-document-check';

    public static string $ability = 'settings';

    public static bool $editable = false;

    public static function canCreate(): bool
    {
        return auth()->user()?->role === 'platform' && (bool) app(BusinessContext::class)->id();
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('number')->default(fn () => 'SUB-'.strtoupper(Str::random(10)))->required()->unique(ignoreRecord: true), Select::make('plan')->options(['basic' => 'Basic', 'professional' => 'Professional', 'enterprise' => 'Enterprise'])->required(), ResourceSchema::money('amount'), DatePicker::make('period_start')->required()->default(today()), DatePicker::make('period_end')->required()->after('period_start')->default(today()->addMonth()),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('number')->searchable(), Tables\Columns\TextColumn::make('plan')->badge(), Tables\Columns\TextColumn::make('amount')->formatStateUsing(fn ($state) => Money::format($state)), Tables\Columns\TextColumn::make('period_end')->date(), Tables\Columns\TextColumn::make('status')->badge(),
        ])->actions([Tables\Actions\Action::make('confirm_payment')->visible(fn ($record) => auth()->user()->role === 'platform' && $record->status === 'pending')->form([TextInput::make('reference')->required()->minLength(3)])->requiresConfirmation()->action(function ($record, $data) {
            DB::transaction(function () use ($record, $data) {
                $invoice = PlatformInvoice::whereKey($record->id)->lockForUpdate()->firstOrFail();
                abort_unless($invoice->status === 'pending', 422);
                $invoice->update(['status' => 'paid', 'payment_reference' => $data['reference']]);
                $business = Business::findOrFail($invoice->business_id);
                $business->update(['plan' => $invoice->plan, 'status' => 'active', 'subscription_ends_at' => $invoice->period_end, 'modules' => array_values(array_intersect($business->modules, Modules::entitlements($invoice->plan)))]);
                Audit::record('subscription.payment_confirmed', 'invoice:'.$invoice->id);
            });
        })]);
    }

    public static function getPages(): array
    {
        return ['index' => PlatformInvoiceResource\Pages\Manage::route('/')];
    }
}
