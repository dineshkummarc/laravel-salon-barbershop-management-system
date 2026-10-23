<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('businesses', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('name_bn')->nullable();
            $t->string('slug')->unique();
            $t->string('phone');
            $t->string('email')->nullable();
            $t->text('description')->nullable();
            $t->json('modules');
            $t->string('plan')->default('professional');
            $t->string('status')->default('active');
            $t->unsignedInteger('tax_basis_points')->default(0);
            $t->string('timezone')->default('Asia/Dhaka');
            $t->timestamps();
        });
        Schema::create('branches', function (Blueprint $t) {
            $this->tenant($t);
            $t->string('name');
            $t->string('address');
            $t->string('phone');
            $t->string('opens_at')->default('09:00');
            $t->string('closes_at')->default('21:00');
            $t->json('disabled_modules')->nullable();
            $t->boolean('active')->default(true);
            $t->timestamps();
        });
        Schema::table('users', function (Blueprint $t) {
            $t->foreignId('business_id')->nullable()->constrained();
            $t->foreignId('branch_id')->nullable()->constrained();
            $t->string('role')->default('customer');
            $t->string('phone')->nullable();
        });
        Schema::create('customers', function (Blueprint $t) {
            $this->tenant($t);
            $t->foreignId('user_id')->nullable()->constrained();
            $t->string('name');
            $t->string('phone');
            $t->string('email')->nullable();
            $t->text('notes')->nullable();
            $t->boolean('marketing_consent')->default(false);
            $t->unsignedInteger('loyalty_points')->default(0);
            $t->timestamps();
            $t->unique(['business_id', 'phone']);
        });
        Schema::create('employees', function (Blueprint $t) {
            $this->tenant($t, true);
            $t->foreignId('user_id')->nullable()->constrained();
            $t->string('name');
            $t->string('title')->default('Stylist');
            $t->string('phone')->nullable();
            $t->unsignedBigInteger('salary')->default(0);
            $t->unsignedInteger('commission_basis_points')->default(1000);
            $t->boolean('active')->default(true);
            $t->timestamps();
        });
        Schema::create('services', function (Blueprint $t) {
            $this->tenant($t);
            $t->string('name');
            $t->string('name_bn')->nullable();
            $t->string('slug');
            $t->string('category')->default('Hair');
            $t->text('description')->nullable();
            $t->unsignedBigInteger('price');
            $t->unsignedInteger('duration')->default(30);
            $t->unsignedInteger('buffer')->default(5);
            $t->boolean('active')->default(true);
            $t->timestamps();
            $t->unique(['business_id', 'slug']);
        });
        Schema::create('products', function (Blueprint $t) {
            $this->tenant($t);
            $t->string('name');
            $t->string('name_bn')->nullable();
            $t->string('slug');
            $t->string('sku');
            $t->string('category')->default('Hair care');
            $t->text('description')->nullable();
            $t->unsignedBigInteger('price');
            $t->unsignedBigInteger('cost')->default(0);
            $t->unsignedInteger('reorder_level')->default(5);
            $t->boolean('active')->default(true);
            $t->timestamps();
            $t->unique(['business_id', 'sku']);
            $t->unique(['business_id', 'slug']);
        });
        Schema::create('stocks', function (Blueprint $t) {
            $this->tenant($t, true);
            $t->foreignId('product_id')->constrained();
            $t->integer('quantity')->default(0);
            $t->timestamps();
            $t->unique(['branch_id', 'product_id']);
        });
        Schema::create('bookings', function (Blueprint $t) {
            $this->tenant($t, true);
            $t->foreignId('customer_id')->constrained();
            $t->foreignId('service_id')->constrained();
            $t->foreignId('employee_id')->constrained();
            $t->dateTime('starts_at');
            $t->dateTime('ends_at');
            $t->string('status')->default('confirmed');
            $t->string('source')->default('online');
            $t->unsignedBigInteger('price');
            $t->text('notes')->nullable();
            $t->timestamps();
            $t->index(['business_id', 'branch_id', 'starts_at']);
            $t->index(['employee_id', 'starts_at', 'ends_at']);
        });
        Schema::create('cash_sessions', function (Blueprint $t) {
            $this->tenant($t, true);
            $t->foreignId('user_id')->constrained();
            $t->unsignedBigInteger('opening_float')->default(0);
            $t->dateTime('opened_at');
            $t->dateTime('closed_at')->nullable();
            $t->bigInteger('counted_cash')->nullable();
            $t->bigInteger('expected_cash')->nullable();
            $t->text('notes')->nullable();
            $t->timestamps();
        });
        Schema::create('sales', function (Blueprint $t) {
            $this->tenant($t, true);
            $t->foreignId('customer_id')->nullable()->constrained();
            $t->foreignId('booking_id')->nullable()->unique()->constrained();
            $t->foreignId('cash_session_id')->nullable()->constrained();
            $t->string('number')->unique();
            $t->uuid('token')->unique();
            $t->string('idempotency_key')->unique();
            $t->string('channel')->default('pos');
            $t->string('status')->default('completed');
            $t->string('fulfillment')->default('collected');
            $t->string('delivery_method')->default('pickup');
            $t->text('delivery_address')->nullable();
            $t->unsignedBigInteger('subtotal');
            $t->unsignedBigInteger('discount')->default(0);
            $t->unsignedBigInteger('tax')->default(0);
            $t->unsignedBigInteger('total');
            $t->unsignedBigInteger('paid')->default(0);
            $t->unsignedBigInteger('refunded')->default(0);
            $t->unsignedBigInteger('commission')->default(0);
            $t->text('notes')->nullable();
            $t->timestamps();
            $t->index(['business_id', 'branch_id', 'created_at']);
        });
        Schema::create('sale_items', function (Blueprint $t) {
            $this->tenant($t, true);
            $t->foreignId('sale_id')->constrained();
            $t->foreignId('product_id')->nullable()->constrained();
            $t->foreignId('service_id')->nullable()->constrained();
            $t->foreignId('employee_id')->nullable()->constrained();
            $t->string('name');
            $t->unsignedInteger('quantity');
            $t->unsignedInteger('returned_quantity')->default(0);
            $t->unsignedBigInteger('unit_price');
            $t->unsignedBigInteger('unit_cost')->default(0);
            $t->unsignedBigInteger('total');
            $t->unsignedBigInteger('commission')->default(0);
            $t->timestamps();
        });
        Schema::create('payments', function (Blueprint $t) {
            $this->tenant($t, true);
            $t->foreignId('sale_id')->constrained();
            $t->string('method');
            $t->string('direction')->default('in');
            $t->unsignedBigInteger('amount');
            $t->string('reference')->nullable();
            $t->string('idempotency_key')->unique();
            $t->foreignId('user_id')->nullable()->constrained();
            $t->timestamps();
        });
        Schema::create('stock_movements', function (Blueprint $t) {
            $this->tenant($t, true);
            $t->foreignId('product_id')->constrained();
            $t->integer('quantity');
            $t->string('reason');
            $t->string('reference');
            $t->foreignId('user_id')->nullable()->constrained();
            $t->timestamps();
        });
        Schema::create('suppliers', function (Blueprint $t) {
            $this->tenant($t);
            $t->string('name');
            $t->string('phone');
            $t->string('email')->nullable();
            $t->text('address')->nullable();
            $t->timestamps();
        });
        Schema::create('purchases', function (Blueprint $t) {
            $this->tenant($t, true);
            $t->foreignId('supplier_id')->constrained();
            $t->foreignId('product_id')->constrained();
            $t->unsignedInteger('quantity');
            $t->unsignedInteger('received')->default(0);
            $t->unsignedBigInteger('unit_cost');
            $t->string('status')->default('ordered');
            $t->unsignedBigInteger('paid')->default(0);
            $t->timestamps();
        });
        Schema::create('transfers', function (Blueprint $t) {
            $this->tenant($t, true);
            $t->foreignId('destination_id')->constrained('branches');
            $t->foreignId('product_id')->constrained();
            $t->unsignedInteger('quantity');
            $t->string('status')->default('requested');
            $t->timestamps();
        });
        Schema::create('expenses', function (Blueprint $t) {
            $this->tenant($t, true);
            $t->string('category');
            $t->string('description');
            $t->unsignedBigInteger('amount');
            $t->date('date');
            $t->string('method')->default('cash');
            $t->string('status')->default('draft');
            $t->timestamps();
        });
        Schema::create('journals', function (Blueprint $t) {
            $this->tenant($t, true);
            $t->string('event_key')->unique();
            $t->string('description');
            $t->timestamps();
        });
        Schema::create('ledger_entries', function (Blueprint $t) {
            $this->tenant($t, true);
            $t->foreignId('journal_id')->constrained();
            $t->string('account');
            $t->unsignedBigInteger('debit')->default(0);
            $t->unsignedBigInteger('credit')->default(0);
            $t->timestamps();
            $t->index(['business_id', 'branch_id', 'account']);
        });
        Schema::create('attendances', function (Blueprint $t) {
            $this->tenant($t, true);
            $t->foreignId('employee_id')->constrained();
            $t->date('date');
            $t->dateTime('check_in');
            $t->dateTime('check_out')->nullable();
            $t->string('source')->default('manual');
            $t->string('external_id')->nullable()->unique();
            $t->timestamps();
            $t->unique(['employee_id', 'date']);
        });
        Schema::create('payrolls', function (Blueprint $t) {
            $this->tenant($t, true);
            $t->foreignId('employee_id')->constrained();
            $t->string('month');
            $t->unsignedBigInteger('salary');
            $t->unsignedBigInteger('commission')->default(0);
            $t->unsignedBigInteger('allowance')->default(0);
            $t->unsignedBigInteger('deduction')->default(0);
            $t->unsignedBigInteger('total');
            $t->string('status')->default('draft');
            $t->dateTime('paid_at')->nullable();
            $t->timestamps();
            $t->unique(['employee_id', 'month']);
        });
        Schema::create('package_plans', function (Blueprint $t) {
            $this->tenant($t);
            $t->string('name');
            $t->foreignId('service_id')->constrained();
            $t->unsignedInteger('uses');
            $t->unsignedInteger('valid_days')->default(90);
            $t->unsignedBigInteger('price');
            $t->boolean('active')->default(true);
            $t->timestamps();
        });
        Schema::create('customer_packages', function (Blueprint $t) {
            $this->tenant($t, true);
            $t->foreignId('customer_id')->constrained();
            $t->foreignId('package_plan_id')->constrained();
            $t->unsignedInteger('remaining');
            $t->unsignedBigInteger('price');
            $t->date('expires_at');
            $t->timestamps();
        });
        Schema::create('package_redemptions', function (Blueprint $t) {
            $this->tenant($t, true);
            $t->foreignId('customer_package_id')->constrained();
            $t->foreignId('booking_id')->unique()->constrained();
            $t->unsignedBigInteger('value');
            $t->timestamps();
        });
        Schema::create('contents', function (Blueprint $t) {
            $this->tenant($t);
            $t->string('title');
            $t->string('slug');
            $t->string('type')->default('page');
            $t->text('body');
            $t->boolean('published')->default(false);
            $t->timestamps();
            $t->unique(['business_id', 'slug']);
        });
        Schema::create('enquiries', function (Blueprint $t) {
            $this->tenant($t);
            $t->string('name');
            $t->string('phone');
            $t->text('message');
            $t->string('status')->default('new');
            $t->timestamps();
        });
        Schema::create('audit_logs', function (Blueprint $t) {
            $this->tenant($t);
            $t->foreignId('user_id')->nullable()->constrained();
            $t->string('action');
            $t->string('subject');
            $t->json('changes')->nullable();
            $t->timestamps();
        });
    }

    private function tenant(Blueprint $t, bool $branch = false): void
    {
        $t->id();
        $t->foreignId('business_id')->constrained();
        if ($branch) {
            $t->foreignId('branch_id')->constrained();
        }
    }

    public function down(): void
    {
        foreach (['audit_logs', 'enquiries', 'contents', 'package_redemptions', 'customer_packages', 'package_plans', 'payrolls', 'attendances', 'ledger_entries', 'journals', 'expenses', 'transfers', 'purchases', 'suppliers', 'stock_movements', 'payments', 'sale_items', 'sales', 'cash_sessions', 'bookings', 'stocks', 'products', 'services', 'employees', 'customers'] as $name) {
            Schema::dropIfExists($name);
        }
        Schema::table('users', function (Blueprint $t) {
            $t->dropConstrainedForeignId('branch_id');
            $t->dropConstrainedForeignId('business_id');
            $t->dropColumn(['role', 'phone']);
        });
        Schema::dropIfExists('branches');
        Schema::dropIfExists('businesses');
    }
};
