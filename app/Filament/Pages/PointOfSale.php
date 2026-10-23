<?php

namespace App\Filament\Pages;

use App\Domain\Checkout;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\Product;
use App\Models\Service;
use App\Support\Modules;
use App\Support\Money;
use App\Support\ResourceSchema;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Str;

class PointOfSale extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';

    protected static ?string $navigationGroup = 'Operations';

    protected static ?int $navigationSort = -10;

    protected static string $view = 'filament.pages.point-of-sale';

    public ?array $data = [];

    public string $requestKey;

    public ?int $bookingId = null;

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->allows('pos') && Modules::enabled('pos');
    }

    public function mount(): void
    {
        abort_unless(static::canAccess(), 403);
        $this->requestKey = (string) Str::uuid();
        $values = ['items' => [['type' => 'service', 'quantity' => 1]], 'payments' => [['method' => 'cash', 'amount' => 0]]];
        if (request('booking')) {
            $b = Booking::findOrFail(request('booking'));
            $this->bookingId = $b->id;
            $values = ['branch_id' => $b->branch_id, 'customer_id' => $b->customer_id, 'items' => [['type' => 'service', 'id' => $b->service_id, 'quantity' => 1, 'employee_id' => $b->employee_id]], 'payments' => [['method' => 'cash', 'amount' => $b->price / 100]]];
        }
        $this->form->fill($values);
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            ResourceSchema::branch(), Select::make('customer_id')->options(fn () => Customer::pluck('name', 'id'))->searchable()->placeholder('Walk-in customer'),
            Repeater::make('items')->schema([
                Select::make('type')->options(['service' => 'Service', 'product' => 'Product'])->required()->live()->afterStateUpdated(fn ($set) => $set('id', null)),
                Select::make('id')->label('Item')->options(fn ($get) => $get('type') === 'product' ? Product::where('active', true)->get()->mapWithKeys(fn ($p) => [$p->id => $p->name.' · '.Money::format($p->price)]) : Service::where('active', true)->get()->mapWithKeys(fn ($s) => [$s->id => $s->name.' · '.Money::format($s->price)]))->required()->searchable(),
                TextInput::make('quantity')->integer()->minValue(1)->maxValue(1000)->default(1)->required(),
                Select::make('employee_id')->options(fn () => Employee::where('active', true)->pluck('name', 'id'))->searchable()->label('Stylist / commission'),
            ])->columns(4)->minItems(1)->maxItems(100)->columnSpanFull()->addActionLabel('Add service or product'),
            TextInput::make('coupon_code')->label('Coupon code')->maxLength(30), TextInput::make('points_redeemed')->label('Loyalty points to redeem')->integer()->minValue(0)->default(0), TextInput::make('tip')->label('Tip (BDT)')->numeric()->minValue(0)->default(0),
            TextInput::make('discount')->label('Discount (BDT)')->numeric()->minValue(0)->default(0)->visible(fn () => auth()->user()->allows('discount')),
            Repeater::make('payments')->schema([
                Select::make('method')->options(['cash' => 'Cash', 'bank' => 'Bank', 'bkash_manual' => 'bKash — verified manually', 'nagad_manual' => 'Nagad — verified manually', 'rocket_manual' => 'Rocket — verified manually', 'gift_card' => 'Gift card'])->required(),
                TextInput::make('amount')->label('Amount (BDT)')->numeric()->minValue(0)->required(), TextInput::make('reference')->label('Verified transfer reference'),
            ])->columns(3)->columnSpanFull()->defaultItems(1)->addActionLabel('Add split payment')->helperText('All totals are calculated on the server. Leave amount 0 to create a due sale.'),
        ])->statePath('data')->columns(2);
    }

    public function checkout(): void
    {
        abort_unless(static::canAccess(), 403);
        $data = $this->form->getState();
        $data['tip'] = Money::parse($data['tip'] ?? 0);
        $data['discount'] = Money::parse($data['discount'] ?? 0);
        $data['payments'] = array_values(array_filter(array_map(fn ($p) => array_merge($p, ['amount' => Money::parse($p['amount'])]), $data['payments'] ?? []), fn ($p) => $p['amount'] > 0));
        $sale = app(Checkout::class)->create($data + ['channel' => 'pos', 'booking_id' => $this->bookingId, 'idempotency_key' => $this->requestKey]);
        Notification::make()->title('Sale '.$sale->number.' completed')->body('Total '.Money::format($sale->total))->success()->send();
        $this->redirect(route('receipt', $sale->id));
    }
}
