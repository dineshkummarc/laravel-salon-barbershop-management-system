<?php

namespace Tests\Feature;

use App\Domain\Appointments;
use App\Domain\Availability;
use App\Domain\Checkout;
use App\Domain\Operations;
use App\Domain\Retention;
use App\Filament\Pages\BusinessSettings;
use App\Filament\Pages\CatalogImport;
use App\Filament\Resources\MembershipResource\Pages\Manage;
use App\Models\CashSession;
use App\Models\MembershipPlan;
use App\Models\PackagePlan;
use App\Models\TimeOff;
use App\Models\User;
use App\Support\BusinessContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class ExtendedAcceptanceTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected function setUp(): void
    {
        parent::setUp();
        config(['salon.demo' => true]);
        $this->actingAs(User::where('email', 'owner@example.test')->first());
        app(BusinessContext::class)->set(1);
    }

    public function test_every_extended_admin_page_renders(): void
    {
        foreach (['assistant', 'catalog-import', 'journal-entry', 'coupons', 'gift-cards', 'membership-plans', 'memberships', 'loyalty-entries', 'reviews', 'campaigns', 'message-deliveries', 'service-recipes', 'stock-adjustments', 'commission-adjustments', 'time-offs', 'credit-notes', 'attendance-devices', 'attendance-events', 'platform-invoices', 'users'] as $slug) {
            $this->get('/admin/'.$slug)->assertOk();
        }
    }

    public function test_membership_and_package_create_actions_use_domain_posting(): void
    {
        $plan = MembershipPlan::first();
        Livewire::test(Manage::class)->callAction('create', data: ['branch_id' => 1, 'customer_id' => 2, 'membership_plan_id' => $plan->id])->assertHasNoActionErrors();
        $this->assertDatabaseHas('memberships', ['customer_id' => 2, 'price' => $plan->price]);
        $p = PackagePlan::first();
        Livewire::test(\App\Filament\Resources\CustomerPackageResource\Pages\Manage::class)->callAction('create', data: ['branch_id' => 1, 'customer_id' => 2, 'package_plan_id' => $p->id])->assertHasNoActionErrors();
        $this->assertDatabaseHas('customer_packages', ['customer_id' => 2, 'remaining' => $p->uses]);
    }

    public function test_catalog_import_saves_unpublished_product(): void
    {
        config(['salon.ai_live' => false]);
        Livewire::test(CatalogImport::class)->set('source', 'An unscented grooming wash.')->call('extract')->assertHasNoErrors()->set('sku', 'IMPORT-001')->set('price', '200')->set('cost', '100')->call('saveDraft')->assertHasNoErrors();
        $this->assertDatabaseHas('products', ['sku' => 'IMPORT-001', 'active' => false, 'price' => 20000]);
    }

    public function test_leave_removes_availability_and_blocks_booking(): void
    {
        $start = now('Asia/Dhaka')->addDays(10)->setTime(10, 0)->utc();
        TimeOff::create(['branch_id' => 1, 'employee_id' => 1, 'starts_at' => $start, 'ends_at' => $start->copy()->addHours(2), 'reason' => 'Leave']);
        $slots = app(Availability::class)->slots(1, 1, 1, $start->copy()->timezone('Asia/Dhaka')->toDateString());
        $this->assertNotContains($start->copy()->timezone('Asia/Dhaka')->format('Y-m-d\TH:i'), array_column($slots, 'value'));
        $this->expectException(ValidationException::class);
        app(Appointments::class)->book(['branch_id' => 1, 'customer_id' => 1, 'service_id' => 1, 'employee_id' => 1, 'starts_at' => $start->toIso8601String()]);
    }

    public function test_branch_assignment_cannot_be_bypassed_in_pos(): void
    {
        $this->actingAs(User::where('email', 'cashier@example.test')->first());
        app(BusinessContext::class)->businessId = null;
        $this->expectException(HttpException::class);
        app(Checkout::class)->create(['branch_id' => 2, 'items' => [['type' => 'service', 'id' => 1, 'quantity' => 1]], 'idempotency_key' => 'wrong-branch']);
    }

    public function test_journal_balancing_and_register_cash_includes_gift_card_issue(): void
    {
        Livewire::test(\App\Filament\Resources\CashSessionResource\Pages\Manage::class)->callAction('create', data: ['branch_id' => 1, 'opening_float' => '1000'])->assertHasNoActionErrors();
        app(Retention::class)->gift(['branch_id' => 1, 'issued_value' => 50000, 'expires_at' => today()->addYear()]);
        $s = CashSession::latest('id')->first();
        app(Operations::class)->closeRegister($s, 150000);
        $this->assertSame(150000, $s->fresh()->expected_cash);
    }

    public function test_module_settings_dependency_validation_runs_on_save(): void
    {
        Livewire::test(BusinessSettings::class)->fillForm(['modules' => ['ecommerce']])->call('save')->assertHasErrors();
    }
}
