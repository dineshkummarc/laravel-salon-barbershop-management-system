<?php

namespace App\Filament\Pages;

use App\Domain\Audit;
use App\Domain\Ledger;
use App\Models\Branch;
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
use Illuminate\Validation\ValidationException;

class JournalEntry extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-pencil-square';

    protected static ?string $navigationGroup = 'Finance';

    protected static string $view = 'filament.pages.journal-entry';

    public ?array $data = [];

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->allows('accounting') && Modules::enabled('accounting');
    }

    public function mount(): void
    {
        $this->form->fill(['lines' => [['debit' => 0, 'credit' => 0], ['debit' => 0, 'credit' => 0]]]);
    }

    public function form(Form $form): Form
    {
        return $form->schema([ResourceSchema::branch(), TextInput::make('description')->required()->maxLength(180), Repeater::make('lines')->schema([Select::make('account')->options(array_combine($a = ['cash', 'bank', 'receivable', 'payable', 'inventory', 'cost_of_goods', 'purchase_price_variance', 'stock_variance', 'expenses', 'revenue', 'tax_payable', 'tips_payable', 'payroll_expense', 'gift_liability', 'package_liability', 'membership_revenue', 'capital', 'retained_earnings', 'branch_clearing'], array_map(fn ($v) => ucwords(str_replace('_', ' ', $v)), $a)))->required(), TextInput::make('debit')->numeric()->minValue(0)->required(), TextInput::make('credit')->numeric()->minValue(0)->required()])->minItems(2)->maxItems(20)->columns(3)->columnSpanFull()])->columns(2)->statePath('data');
    }

    public function post(): void
    {
        abort_unless(static::canAccess(), 403);
        $data = $this->form->getState();
        Branch::findOrFail($data['branch_id']);
        $lines = [];
        foreach ($data['lines'] as $line) {
            $debit = Money::parse($line['debit']);
            $credit = Money::parse($line['credit']);
            if (($debit && $credit) || (! $debit && ! $credit)) {
                throw ValidationException::withMessages(['data.lines' => 'Each line must have a debit or a credit, but not both.']);
            }$lines[$line['account']] = ($lines[$line['account']] ?? 0) + $debit - $credit;
        }
        app(Ledger::class)->post($data['branch_id'], 'manual:'.Str::uuid(), $data['description'], $lines);
        Audit::record('journal.manual', 'ledger', ['description' => $data['description']]);
        Notification::make()->title('Balanced journal posted')->success()->send();
        $this->form->fill();
    }
}
