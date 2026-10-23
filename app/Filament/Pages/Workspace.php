<?php

namespace App\Filament\Pages;

use App\Domain\Audit;
use App\Models\Branch;
use App\Models\Business;
use App\Support\BusinessContext;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Page;

class Workspace extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-arrows-right-left';

    protected static ?int $navigationSort = -20;

    protected static string $view = 'filament.pages.workspace';

    public ?array $data = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->role !== 'customer';
    }

    public function mount(): void
    {
        $this->form->fill(['business_id' => app(BusinessContext::class)->id(), 'branch_id' => app(BusinessContext::class)->branchId()]);
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            Select::make('business_id')->options(fn () => Business::pluck('name', 'id'))->visible(fn () => auth()->user()->role === 'platform')->live(),
            Select::make('branch_id')->options(fn ($get) => Branch::withoutGlobalScopes()->where('business_id', auth()->user()->role === 'platform' ? $get('business_id') : auth()->user()->business_id)->when(auth()->user()->branch_id, fn ($q) => $q->whereKey(auth()->user()->branch_id))->pluck('name', 'id'))->placeholder('All authorized branches'),
        ])->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();
        $business = auth()->user()->role === 'platform' ? (int) ($data['business_id'] ?? 0) : auth()->user()->business_id;
        Business::findOrFail($business);
        $branch = auth()->user()->branch_id ?: ($data['branch_id'] ?? null);
        if ($branch) {
            Branch::withoutGlobalScopes()->where('business_id', $business)->findOrFail($branch);
        }
        session(['business_id' => $business, 'branch_id' => $branch]);
        app(BusinessContext::class)->set($business);
        Audit::record('workspace.selected', 'business:'.$business, ['branch_id' => $branch]);
        $this->redirect('/admin');
    }
}
