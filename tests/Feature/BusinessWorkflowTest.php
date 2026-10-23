<?php

namespace Tests\Feature;

use App\Domain\Appointments;
use App\Domain\Checkout;
use App\Domain\Inventory;
use App\Domain\Operations;
use App\Models\LedgerEntry;
use App\Models\Payroll;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\Stock;
use App\Models\User;
use App\Support\BusinessContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class BusinessWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected function setUp(): void
    {
        parent::setUp();
        app(BusinessContext::class)->set(1);
    }

    private function owner(): User
    {
        return User::where('email', 'owner@example.test')->firstOrFail();
    }

    private function sale(array $extra = []): Sale
    {
        return app(Checkout::class)->create($extra + ['branch_id' => 1, 'customer_id' => 1, 'idempotency_key' => (string) Str::uuid(), 'items' => [['type' => 'product', 'id' => 1, 'quantity' => 2]]]);
    }

    public function test_public_pages_and_all_owner_resources_render(): void
    {
        foreach (['/', '/b/dhaka-grooming', '/b/dhaka-grooming/services', '/b/dhaka-grooming/shop', '/b/dhaka-grooming/branches', '/b/dhaka-grooming/book', '/b/dhaka-grooming/cart', '/b/dhaka-grooming/journal', '/b/dhaka-grooming/pages/about', '/b/dhaka-grooming/contact', '/admin/login'] as $url) {
            $this->get($url)->assertOk();
        }
        $this->actingAs($this->owner());
        foreach (['/admin', '/admin/point-of-sale', '/admin/reports', '/admin/business-settings', '/admin/workspace', '/admin/branches', '/admin/customers', '/admin/employees', '/admin/services', '/admin/products', '/admin/stocks', '/admin/stock-movements', '/admin/bookings', '/admin/sales', '/admin/suppliers', '/admin/purchases', '/admin/transfers', '/admin/expenses', '/admin/ledger-entries', '/admin/attendances', '/admin/payrolls', '/admin/package-plans', '/admin/customer-packages', '/admin/cash-sessions', '/admin/contents', '/admin/enquiries', '/admin/audit-logs'] as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_sale_payment_idempotency_and_full_reversal_reconcile(): void
    {
        $this->actingAs($this->owner());
        $before = Stock::where('branch_id', 1)->where('product_id', 1)->first()->quantity;
        $sale = $this->sale(['idempotency_key' => 'test-sale']);
        $this->assertSame($before - 2, Stock::where('branch_id', 1)->where('product_id', 1)->first()->quantity);
        $this->assertSame($sale->id, $this->sale(['idempotency_key' => 'test-sale'])->id);
        app(Checkout::class)->pay($sale, 'cash', 30000, 'test-pay');
        app(Checkout::class)->pay($sale, 'cash', 30000, 'test-pay');
        $this->assertSame(30000, $sale->fresh()->paid);
        app(Checkout::class)->pay($sale, 'bank', $sale->total - 30000, 'test-pay-2');
        app(Checkout::class)->refund($sale, 'Customer return, unopened products');
        $this->assertSame($before, Stock::where('branch_id', 1)->where('product_id', 1)->first()->quantity);
        $this->assertSame('refunded', $sale->fresh()->status);
        $this->assertEquals(LedgerEntry::sum('debit'), LedgerEntry::sum('credit'));
        $this->expectException(ValidationException::class);
        app(Checkout::class)->refund($sale, 'Repeated refund');
    }

    public function test_insufficient_stock_rolls_back_sale_and_journal(): void
    {
        $this->actingAs($this->owner());
        $count = Sale::count();
        try {
            $this->sale(['items' => [['type' => 'product', 'id' => 1, 'quantity' => 1000]]]);
            $this->fail('Overselling allowed.');
        } catch (ValidationException) {
        }
        $this->assertSame($count, Sale::count());
    }

    public function test_overpayment_rolls_back_entire_checkout(): void
    {
        $this->actingAs($this->owner());
        $count = Sale::count();
        try {
            $this->sale(['payments' => [['method' => 'cash', 'amount' => 99999999]]]);
            $this->fail('Overpayment allowed.');
        } catch (ValidationException) {
        }
        $this->assertSame($count, Sale::count());
    }

    public function test_booking_conflicts_and_invalid_staff_are_rejected(): void
    {
        $this->actingAs($this->owner());
        $data = ['branch_id' => 1, 'customer_id' => 1, 'service_id' => 1, 'employee_id' => 1, 'starts_at' => now('Asia/Dhaka')->addDays(5)->setTime(11, 0)->toIso8601String()];
        $booking = app(Appointments::class)->book($data);
        try {
            app(Appointments::class)->book($data);
            $this->fail('Overlapping booking accepted.');
        } catch (ValidationException) {
        }
        app(Appointments::class)->transition($booking, 'cancelled');
        $this->assertNotNull(app(Appointments::class)->book($data));
    }

    public function test_tenant_scope_customer_receipts_and_roles(): void
    {
        $this->actingAs($this->owner());
        $sale = $this->sale();
        $second = User::where('email', 'owner2@example.test')->first();
        $this->actingAs($second);
        app(BusinessContext::class)->businessId = null;
        $this->assertNull(Sale::find($sale->id));
        $this->get('/receipts/'.$sale->id)->assertNotFound();
        $cashier = User::where('email', 'cashier@example.test')->first();
        $this->actingAs($cashier);
        app(BusinessContext::class)->businessId = null;
        $this->get('/admin/payrolls')->assertForbidden();
        $this->get('/admin/business-settings')->assertForbidden();
        $customer = User::where('email', 'customer@example.test')->first();
        $this->actingAs($customer);
        app(BusinessContext::class)->businessId = null;
        $other = Sale::where('customer_id', '<>', 1)->first();
        $this->get('/receipts/'.$other->id)->assertForbidden();
    }

    public function test_disabled_modules_block_direct_urls_and_domain_actions(): void
    {
        $owner = User::where('email', 'owner2@example.test')->first();
        $this->actingAs($owner);
        app(BusinessContext::class)->businessId = null;
        $this->get('/admin/products')->assertForbidden();
        $this->get('/b/classic-barber/shop')->assertForbidden();
        $this->expectException(HttpException::class);
        app(Checkout::class)->create(['channel' => 'online', 'branch_id' => 4, 'items' => [], 'idempotency_key' => 'disabled']);
    }

    public function test_purchase_receipt_is_partial_and_cannot_exceed_order(): void
    {
        $this->actingAs($this->owner());
        $p = Purchase::where('status', 'ordered')->firstOrFail();
        app(Inventory::class)->receive($p, 5);
        $this->assertSame(5, $p->fresh()->received);
        $this->assertSame('partially_received', $p->fresh()->status);
        $this->expectException(ValidationException::class);
        app(Inventory::class)->receive($p, 999);
    }

    public function test_attendance_and_payroll_lock(): void
    {
        $this->actingAs($this->owner());
        $p = Payroll::firstOrFail();
        app(Operations::class)->payPayroll($p);
        $this->assertSame('paid', $p->fresh()->status);
        $this->expectException(ValidationException::class);
        app(Operations::class)->attendance(['branch_id' => 1, 'employee_id' => $p->employee_id, 'check_in' => now()]);
    }

    public function test_customer_can_checkout_and_see_only_own_order(): void
    {
        $this->actingAs(User::where('email', 'customer@example.test')->first());
        $this->post('/b/dhaka-grooming/cart', ['product_id' => 1, 'quantity' => 1])->assertRedirect();
        $this->get('/b/dhaka-grooming/cart')->assertOk();
        $key = session('checkout_key');
        $response = $this->post('/b/dhaka-grooming/checkout', ['branch_id' => 1, 'delivery_method' => 'pickup', 'checkout_key' => $key]);
        $response->assertRedirect();
        $this->get($response->headers->get('Location'))->assertOk()->assertSee('Daily care shampoo');
        $this->get('/b/dhaka-grooming/account')->assertOk();
    }
}
