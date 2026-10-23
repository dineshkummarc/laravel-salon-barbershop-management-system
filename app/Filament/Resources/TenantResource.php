<?php

namespace App\Filament\Resources;

use App\Domain\Appointments;
use App\Domain\Audit;
use App\Domain\Checkout;
use App\Domain\DeviceSync;
use App\Domain\Inventory;
use App\Domain\Messaging;
use App\Domain\Operations;
use App\Domain\StockControl;
use App\Models\Booking;
use App\Models\Branch;
use App\Support\Modules;
use App\Support\Money;
use App\Support\ResourceSchema;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

abstract class TenantResource extends Resource
{
    public static ?string $module = null;

    public static string $ability = 'catalog';

    public static bool $editable = true;

    public static bool $creatable = true;

    public static function modelName(): string
    {
        return class_basename(static::$model);
    }

    public static function canViewAny(): bool
    {
        return (bool) auth()->user()?->allows(static::$ability) && Modules::enabled(static::$module);
    }

    public static function canCreate(): bool
    {
        return static::canViewAny() && static::$creatable;
    }

    public static function canEdit(Model $record): bool
    {
        return static::canViewAny() && static::$editable;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canView(Model $record): bool
    {
        return static::canViewAny();
    }

    public static function form(Form $form): Form
    {
        return $form->schema(ResourceSchema::fields(static::modelName()))->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->columns(ResourceSchema::columns(static::modelName()))->defaultSort('id', 'desc')->filters([
            Tables\Filters\SelectFilter::make('branch_id')->label('Branch')->options(fn () => Branch::pluck('name', 'id'))->visible(fn () => (new (static::$model))->branchScoped),
        ])->actions(static::recordActions())->emptyStateHeading('No records yet')->emptyStateDescription('Create a record to get started.')->paginated([10, 25, 50]);
    }

    protected static function recordActions(): array
    {
        $actions = [];
        if (static::$editable) {
            $actions[] = Tables\Actions\EditAction::make();
        }
        $run = fn ($name, $label, $callback) => Tables\Actions\Action::make($name)->label($label)->requiresConfirmation()->action(function ($record, array $data) use ($callback) {
            abort_unless(static::canViewAny(), 403);
            $callback($record, $data);
            Notification::make()->title('Saved successfully')->success()->send();
        });
        switch (static::modelName()) {
            case 'AttendanceDevice':$actions[] = $run('sync', 'Sync device', fn ($record) => app(DeviceSync::class)->sync($record));
                break;
            case 'StockAdjustment': $actions[] = $run('post', 'Post adjustment', fn ($record) => app(StockControl::class)->adjust($record))->visible(fn ($record) => $record->status === 'draft');
                break;
            case 'Campaign': $actions[] = $run('send', 'Queue campaign', fn ($record) => app(Messaging::class)->campaign($record))->visible(fn ($record) => $record->status === 'draft');
                break;
            case 'Review':
                foreach (['published', 'rejected'] as $state) {
                    $actions[] = $run($state, ucfirst($state), function ($record) use ($state) {
                        $record->update(['status' => $state]);
                        Audit::record('review.'.$state, 'review:'.$record->id);
                    })->visible(fn ($record) => $record->status === 'pending');
                }break;
            case 'Membership':$actions[] = $run('cancel', 'Cancel benefits', fn ($record) => $record->update(['status' => 'cancelled']))->visible(fn ($record) => $record->status === 'active');
                break;
            case 'Booking':
                foreach (['checked_in' => 'Check in', 'in_service' => 'Start service', 'completed' => 'Complete', 'cancelled' => 'Cancel', 'no_show' => 'No-show'] as $state => $label) {
                    $allowed = match ($state) {
                        'checked_in' => ['confirmed'],'in_service' => ['checked_in'],'completed' => ['in_service'],'cancelled' => ['confirmed', 'checked_in'],default => ['confirmed']
                    };
                    $actions[] = $run($state, $label, fn ($record) => app(Appointments::class)->transition($record, $state))->visible(fn ($record) => in_array($record->status, $allowed));
                }
                $actions[] = Tables\Actions\Action::make('bill')->label('Bill in POS')->url(fn ($record) => route('filament.admin.pages.point-of-sale', ['booking' => $record->id]))->visible(fn ($record) => $record->status === 'completed' && ! $record->sale && auth()->user()->allows('pos'));
                break;
            case 'Sale':
                $actions[] = Tables\Actions\Action::make('receipt')->url(fn ($record) => route('receipt', $record->id))->openUrlInNewTab();
                $actions[] = $run('payment', 'Collect payment', fn ($record, $d) => app(Checkout::class)->pay($record, $d['method'], Money::parse($d['amount']), (string) Str::uuid(), $d['reference'] ?? null))->form([TextInput::make('amount')->numeric()->required(), Select::make('method')->options(['cash' => 'Cash', 'bank' => 'Bank', 'bkash_manual' => 'bKash (manually verified)', 'nagad_manual' => 'Nagad (manually verified)', 'gift_card' => 'Gift card'])->required(), TextInput::make('reference')])->visible(fn ($record) => $record->due > 0 && ! in_array($record->status, ['refunded', 'cancelled']) && auth()->user()->allows('pos'));
                $actions[] = $run('partial_return', 'Partial return', function ($record, $data) {
                    $quantities = [];
                    foreach ($data['items'] as $line) {
                        $id = (int) $line['sale_item_id'];
                        $quantities[$id] = ($quantities[$id] ?? 0) + (int) $line['quantity'];
                    }app(Checkout::class)->refundItems($record, $quantities, $data['reason'], (string) Str::uuid());
                })->form([Repeater::make('items')->schema([Select::make('sale_item_id')->options(fn ($record) => $record->items()->whereColumn('returned_quantity', '<', 'quantity')->pluck('name', 'id'))->required(), TextInput::make('quantity')->integer()->minValue(1)->required()])->minItems(1)->required(), Textarea::make('reason')->required()->minLength(3)])->visible(fn ($record) => ! in_array($record->status, ['refunded', 'cancelled']) && auth()->user()->allows('refund'));
                $actions[] = $run('reverse', 'Refund / cancel', fn ($record, $d) => app(Checkout::class)->refund($record, $d['reason']))->form([Textarea::make('reason')->required()->minLength(3)])->color('danger')->visible(fn ($record) => ! in_array($record->status, ['refunded', 'cancelled']) && auth()->user()->allows('refund'));
                foreach (['processing', 'ready', 'dispatched', 'collected', 'delivered'] as $state) {
                    $actions[] = $run('fulfill_'.$state, ucfirst($state), fn ($record) => app(Checkout::class)->fulfill($record, $state))->visible(fn ($record) => $record->channel === 'online' && ! in_array($record->status, ['refunded', 'cancelled']) && match ($state) {
                        'processing' => $record->fulfillment === 'pending','ready','dispatched' => $record->fulfillment === 'processing','collected' => $record->fulfillment === 'ready','delivered' => $record->fulfillment === 'dispatched'
                    });
                }
                break;
            case 'Purchase': $actions[] = $run('receive', 'Receive stock', fn ($record, $d) => app(Inventory::class)->receive($record, (int) $d['quantity']))->form([TextInput::make('quantity')->integer()->minValue(1)->required()])->visible(fn ($record) => $record->received < $record->quantity);
                break;
            case 'Transfer':
                $actions[] = $run('dispatch', 'Dispatch', fn ($record) => app(Inventory::class)->dispatch($record))->visible(fn ($record) => $record->status === 'requested');
                $actions[] = $run('receive', 'Receive', fn ($record) => app(Inventory::class)->receiveTransfer($record))->visible(fn ($record) => $record->status === 'dispatched' && ! auth()->user()->branch_id);
                break;
            case 'Expense': $actions[] = $run('post', 'Post expense', fn ($record) => app(Operations::class)->expense($record))->visible(fn ($record) => $record->status === 'draft');
                break;
            case 'Payroll': $actions[] = $run('pay', 'Approve & pay', fn ($record) => app(Operations::class)->payPayroll($record))->visible(fn ($record) => $record->status === 'draft');
                break;
            case 'CashSession': $actions[] = $run('close', 'Close register', fn ($record, $d) => app(Operations::class)->closeRegister($record, Money::parse($d['counted_cash'])))->form([TextInput::make('counted_cash')->numeric()->required()])->visible(fn ($record) => ! $record->closed_at && ($record->user_id === auth()->id() || auth()->user()->allows('settings')));
                break;
            case 'CustomerPackage': $actions[] = $run('redeem', 'Redeem appointment', fn ($record, $d) => app(Operations::class)->redeem($record, Booking::findOrFail($d['booking_id'])))->form([Select::make('booking_id')->options(fn ($record) => Booking::where('customer_id', $record->customer_id)->where('status', 'completed')->pluck('id', 'id'))->required()])->visible(fn ($record) => $record->remaining > 0);
                break;
        }

        return $actions;
    }
}
