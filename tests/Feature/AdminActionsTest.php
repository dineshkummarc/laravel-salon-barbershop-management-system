<?php

namespace Tests\Feature;

use App\Filament\Pages\Assistant;
use App\Filament\Pages\PointOfSale;
use App\Filament\Resources\CustomerResource\Pages\Manage;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\Sale;
use App\Models\User;
use App\Support\BusinessContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminActionsTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::where('email', 'owner@example.test')->first());
        app(BusinessContext::class)->set(1);
    }

    public function test_customer_create_action_persists_a_scoped_record(): void
    {
        Livewire::test(Manage::class)->callAction('create', data: ['name' => 'Test Customer', 'phone' => '+8801712345678', 'email' => 'test@example.test', 'notes' => '', 'marketing_consent' => false])->assertHasNoActionErrors();
        $this->assertNotNull(Customer::where('phone', '+8801712345678')->first());
    }

    public function test_pos_form_creates_sale_with_server_prices(): void
    {
        $count = Sale::count();
        Livewire::test(PointOfSale::class)->fillForm(['branch_id' => 1, 'customer_id' => 1, 'items' => [['type' => 'service', 'id' => 1, 'quantity' => 1, 'employee_id' => 1]], 'discount' => 0, 'payments' => [['method' => 'cash', 'amount' => 350]]])->call('checkout')->assertHasNoFormErrors()->assertRedirect();
        $this->assertSame($count + 1, Sale::count());
        $this->assertSame(35000, Sale::latest('id')->first()->total);
    }

    public function test_booking_create_action_uses_conflict_service_and_transition_action(): void
    {
        $component = Livewire::test(\App\Filament\Resources\BookingResource\Pages\Manage::class)->callAction('create', data: ['branch_id' => 1, 'customer_id' => 1, 'service_id' => 1, 'employee_id' => 1, 'starts_at' => now('Asia/Dhaka')->addDays(10)->setTime(11, 0)->format('Y-m-d H:i:s'), 'source' => 'reception', 'notes' => ''])->assertHasNoActionErrors();
        $b = Booking::latest('id')->first();
        $this->assertSame('confirmed', $b->status);
        $component->callTableAction('checked_in', $b)->assertHasNoTableActionErrors();
        $this->assertSame('checked_in', $b->fresh()->status);
    }

    public function test_assistant_fake_returns_a_visible_draft(): void
    {
        config(['salon.ai_live' => false]);
        Livewire::test(Assistant::class)->set('topic', 'A welcoming salon')->call('generate')->assertHasNoErrors()->assertSee('DEMO DRAFT');
    }

    public function test_csv_and_pdf_are_authorized_and_real_documents(): void
    {
        $this->get('/reports/export?from='.now()->startOfMonth()->toDateString().'&to='.now()->toDateString())->assertOk()->assertDownload('ledger.csv');
        $sale = Sale::first();
        $response = $this->get('/receipts/'.$sale->id.'/pdf');
        $response->assertOk();
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }
}
