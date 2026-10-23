<?php

namespace Tests\Feature;

use App\Domain\Checkout;
use App\Domain\DeviceSync;
use App\Domain\Retention;
use App\Models\AttendanceDevice;
use App\Models\AttendanceEvent;
use App\Models\Coupon;
use App\Models\Customer;
use App\Models\LedgerEntry;
use App\Models\Sale;
use App\Models\Stock;
use App\Models\User;
use App\Support\BusinessContext;
use App\Support\SafeCatalogReader;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class RetentionTest extends TestCase
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

    private function sale(int $qty = 2, array $extra = []): Sale
    {
        return app(Checkout::class)->create($extra + ['branch_id' => 1, 'customer_id' => 1, 'idempotency_key' => (string) Str::uuid(), 'items' => [['type' => 'product', 'id' => 1, 'quantity' => $qty]]]);
    }

    public function test_partial_returns_do_not_over_refund_or_duplicate_stock(): void
    {
        $before = Stock::where('branch_id', 1)->where('product_id', 1)->value('quantity');
        $sale = $this->sale();
        app(Checkout::class)->pay($sale, 'cash', $sale->total, 'return-payment');
        $item = $sale->items->first();
        $note = app(Checkout::class)->refundItems($sale, [$item->id => 1], 'Unopened return', 'partial1');
        $this->assertSame(65000, $note->amount);
        $this->assertSame(65000, $sale->fresh()->refunded);
        $this->assertSame($before - 1, Stock::where('branch_id', 1)->where('product_id', 1)->value('quantity'));
        $this->assertSame($note->id, app(Checkout::class)->refundItems($sale, [$item->id => 1], 'Unopened return', 'partial1')->id);
        app(Checkout::class)->refund($sale->fresh(), 'Return remaining item');
        $this->assertSame(130000, $sale->fresh()->refunded);
        $this->assertSame($before, Stock::where('branch_id', 1)->where('product_id', 1)->value('quantity'));
        $this->assertEquals(LedgerEntry::sum('debit'), LedgerEntry::sum('credit'));
    }

    public function test_partial_credit_on_due_sale_reduces_due_without_cash_payout(): void
    {
        $sale = $this->sale();
        $item = $sale->items->first();
        app(Checkout::class)->refundItems($sale, [$item->id => 1], 'Cancelled one item', 'due-partial');
        $this->assertSame(65000, $sale->fresh()->due);
        $this->assertSame(0, $sale->fresh()->refunded);
    }

    public function test_coupon_membership_and_loyalty_use_server_rules(): void
    {
        Coupon::create(['code' => 'WELCOME10', 'name' => 'Welcome', 'percent' => 10, 'usage_limit' => 2, 'expires_at' => today()->addDays(3)]);
        $customer = Customer::find(1);
        $customer->update(['loyalty_points' => 100]);
        $sale = $this->sale(2, ['coupon_code' => 'WELCOME10', 'points_redeemed' => 20]);
        $this->assertSame(115000, $sale->total);
        $this->assertSame(1, Coupon::where('code', 'WELCOME10')->value('used'));
        app(Checkout::class)->refund($sale, 'Full cancellation');
        $this->assertSame(100, Customer::find(1)->loyalty_points);
        $this->assertSame(0, Coupon::where('code', 'WELCOME10')->value('used'));
    }

    public function test_gift_card_tender_and_refund_restore_liability_balance(): void
    {
        $card = app(Retention::class)->gift(['branch_id' => 1, 'customer_id' => 1, 'issued_value' => 200000, 'expires_at' => today()->addYear()]);
        $sale = $this->sale();
        app(Checkout::class)->pay($sale, 'gift_card', $sale->total, 'gift-payment', $card->code);
        $this->assertSame(70000, $card->fresh()->balance);
        app(Checkout::class)->refund($sale, 'Gift card purchase return');
        $this->assertSame(200000, $card->fresh()->balance);
    }

    public function test_attendance_device_simulation_is_deduplicated(): void
    {
        $device = AttendanceDevice::create(['branch_id' => 1, 'name' => 'Demo machine', 'provider' => 'fake', 'employee_map' => ['101' => 1, '102' => 2]]);
        $this->assertSame(4, app(DeviceSync::class)->sync($device));
        $this->assertSame(0, app(DeviceSync::class)->sync($device));
        $this->assertSame(4, AttendanceEvent::count());
    }

    public function test_import_blocks_internal_hosts_and_creates_unpublished_draft(): void
    {
        $this->expectException(ValidationException::class);
        app(SafeCatalogReader::class)->fetch('https://127.0.0.1/admin');
    }
}
