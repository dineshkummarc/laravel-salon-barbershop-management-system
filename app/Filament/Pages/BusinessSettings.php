<?php

namespace App\Filament\Pages;

use App\Domain\Audit;
use App\Support\BusinessContext;
use App\Support\Modules;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class BusinessSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-adjustments-horizontal';

    protected static ?string $navigationGroup = 'Business';

    protected static string $view = 'filament.pages.business-settings';

    public ?array $data = [];

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->allows('settings') && app(BusinessContext::class)->id() !== null;
    }

    public function mount(): void
    {
        abort_unless(static::canAccess(), 403);
        $this->form->fill(app(BusinessContext::class)->business()->toArray());
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            Select::make('preset')->options(['basic' => 'Basic barber', 'salon' => 'Appointments salon', 'retail' => 'Retail salon', 'full' => 'Full studio / chain'])->dehydrated(false)->live()->afterStateUpdated(function ($state, $set) {
                $presets = ['basic' => ['website', 'services', 'pos', 'crm', 'hr', 'content', 'reports'], 'salon' => ['website', 'services', 'pos', 'crm', 'hr', 'content', 'reports', 'appointments'], 'retail' => ['website', 'services', 'pos', 'crm', 'hr', 'content', 'reports', 'products', 'inventory', 'purchasing', 'ecommerce'], 'full' => array_keys(Modules::ALL)];
                $set('modules', $presets[$state] ?? []);
            }),
            TextInput::make('name')->required(), TextInput::make('name_bn'), TextInput::make('phone')->required(), TextInput::make('email')->email(), Textarea::make('description')->columnSpanFull(),
            TextInput::make('tax_basis_points')->integer()->minValue(0)->maxValue(10000)->required()->helperText('0 by default. 500 = 5%. Verify your applicable obligations before setting.'),
            CheckboxList::make('modules')->options(Modules::ALL)->columns(3)->columnSpanFull()->helperText('Disabling a module preserves records. New actions are blocked; re-enable it to process outstanding obligations.'),
        ])->columns(2)->statePath('data');
    }

    public function save(): void
    {
        abort_unless(static::canAccess(), 403);
        $data = $this->form->getState();
        Modules::validate($data['modules'] ?? []);
        app(BusinessContext::class)->business()->update($data);
        Audit::record('settings.updated', 'business:'.app(BusinessContext::class)->id(), ['modules' => $data['modules']]);
        Notification::make()->title('Business settings saved')->success()->send();
    }
}
