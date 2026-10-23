<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private function base(Blueprint $t, bool $branch = false): void
    {
        $t->id();
        $t->foreignId('business_id')->constrained();
        if ($branch) {
            $t->foreignId('branch_id')->constrained();
        }
    }

    public function up(): void
    {
        Schema::table('sales', function (Blueprint $t) {
            $t->unsignedInteger('points_redeemed')->default(0);
            $t->string('coupon_code')->nullable();
            $t->unsignedBigInteger('tip')->default(0);
        });
        Schema::table('employees', function (Blueprint $t) {
            $t->json('working_days')->nullable();
            $t->string('shift_start')->default('09:00');
            $t->string('shift_end')->default('21:00');
        });
        Schema::table('bookings', function (Blueprint $t) {
            $t->boolean('home_service')->default(false);
            $t->text('home_address')->nullable();
            $t->unsignedInteger('travel_minutes')->default(0);
        });
        Schema::create('time_offs', function (Blueprint $t) {
            $this->base($t, true);
            $t->foreignId('employee_id')->constrained();
            $t->dateTime('starts_at');
            $t->dateTime('ends_at');
            $t->string('reason');
            $t->timestamps();
        });
        Schema::create('coupons', function (Blueprint $t) {
            $this->base($t);
            $t->string('code');
            $t->string('name');
            $t->unsignedInteger('percent');
            $t->unsignedInteger('usage_limit')->default(100);
            $t->unsignedInteger('used')->default(0);
            $t->date('expires_at');
            $t->boolean('active')->default(true);
            $t->timestamps();
            $t->unique(['business_id', 'code']);
        });
        Schema::create('gift_cards', function (Blueprint $t) {
            $this->base($t, true);
            $t->string('code')->unique();
            $t->foreignId('customer_id')->nullable()->constrained();
            $t->unsignedBigInteger('issued_value');
            $t->unsignedBigInteger('balance');
            $t->date('expires_at');
            $t->timestamps();
        });
        Schema::table('payments', function (Blueprint $t) {
            $t->foreignId('gift_card_id')->nullable()->constrained();
        });
        Schema::create('membership_plans', function (Blueprint $t) {
            $this->base($t);
            $t->string('name');
            $t->unsignedBigInteger('price');
            $t->unsignedInteger('days')->default(30);
            $t->unsignedInteger('discount_percent')->default(10);
            $t->boolean('active')->default(true);
            $t->timestamps();
        });
        Schema::create('memberships', function (Blueprint $t) {
            $this->base($t, true);
            $t->foreignId('customer_id')->constrained();
            $t->foreignId('membership_plan_id')->constrained();
            $t->date('starts_at');
            $t->date('ends_at');
            $t->unsignedInteger('discount_percent');
            $t->unsignedBigInteger('price');
            $t->string('status')->default('active');
            $t->timestamps();
        });
        Schema::create('loyalty_entries', function (Blueprint $t) {
            $this->base($t, true);
            $t->foreignId('customer_id')->constrained();
            $t->foreignId('sale_id')->constrained();
            $t->integer('points');
            $t->string('reason');
            $t->timestamps();
            $t->unique(['sale_id', 'reason']);
        });
        Schema::create('reviews', function (Blueprint $t) {
            $this->base($t);
            $t->foreignId('customer_id')->constrained();
            $t->foreignId('sale_id')->unique()->constrained();
            $t->unsignedInteger('rating');
            $t->text('body');
            $t->string('status')->default('pending');
            $t->timestamps();
        });
        Schema::create('campaigns', function (Blueprint $t) {
            $this->base($t);
            $t->string('title');
            $t->text('body');
            $t->string('status')->default('draft');
            $t->unsignedInteger('sent')->default(0);
            $t->timestamps();
        });
        Schema::create('message_deliveries', function (Blueprint $t) {
            $this->base($t);
            $t->foreignId('campaign_id')->constrained();
            $t->foreignId('customer_id')->constrained();
            $t->string('status')->default('pending');
            $t->text('error')->nullable();
            $t->timestamps();
            $t->unique(['campaign_id', 'customer_id']);
        });
        Schema::create('service_recipes', function (Blueprint $t) {
            $this->base($t);
            $t->foreignId('service_id')->constrained();
            $t->foreignId('product_id')->constrained();
            $t->unsignedInteger('quantity');
            $t->timestamps();
            $t->unique(['business_id', 'service_id', 'product_id']);
        });
        Schema::create('stock_adjustments', function (Blueprint $t) {
            $this->base($t, true);
            $t->foreignId('product_id')->constrained();
            $t->integer('quantity');
            $t->string('reason');
            $t->string('status')->default('draft');
            $t->timestamps();
        });
        Schema::create('commission_adjustments', function (Blueprint $t) {
            $this->base($t, true);
            $t->foreignId('employee_id')->constrained();
            $t->foreignId('sale_id')->constrained();
            $t->bigInteger('amount');
            $t->string('month');
            $t->timestamps();
            $t->unique(['sale_id', 'employee_id']);
        });
        Schema::create('platform_invoices', function (Blueprint $t) {
            $this->base($t);
            $t->string('number')->unique();
            $t->string('plan');
            $t->unsignedBigInteger('amount');
            $t->date('period_start');
            $t->date('period_end');
            $t->string('status')->default('pending');
            $t->string('payment_reference')->nullable();
            $t->timestamps();
        });
        Schema::create('reminder_deliveries', function (Blueprint $t) {
            $this->base($t, true);
            $t->foreignId('booking_id')->unique()->constrained();
            $t->string('status')->default('pending');
            $t->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['reminder_deliveries', 'platform_invoices', 'commission_adjustments', 'stock_adjustments', 'service_recipes', 'message_deliveries', 'campaigns', 'reviews', 'loyalty_entries', 'memberships', 'membership_plans'] as $name) {
            Schema::dropIfExists($name);
        }
        Schema::table('payments', fn (Blueprint $t) => $t->dropConstrainedForeignId('gift_card_id'));
        Schema::dropIfExists('gift_cards');
        Schema::dropIfExists('coupons');
        Schema::dropIfExists('time_offs');
        Schema::table('sales', fn (Blueprint $t) => $t->dropColumn(['points_redeemed', 'coupon_code', 'tip']));
        Schema::table('employees', fn (Blueprint $t) => $t->dropColumn(['working_days', 'shift_start', 'shift_end']));
        Schema::table('bookings', fn (Blueprint $t) => $t->dropColumn(['home_service', 'home_address', 'travel_minutes']));
    }
};
