<x-filament-panels::page>
    <x-filament::section heading="A good day starts with a great service" description="Sell services and products together. Select the branch before adding items. Prices are in BDT.">
        <form wire:submit="checkout" class="space-y-6">
            {{ $this->form }}
            <x-filament::button type="submit" icon="heroicon-o-check-circle" wire:loading.attr="disabled">Complete sale & view receipt</x-filament::button>
        </form>
    </x-filament::section>
</x-filament-panels::page>
