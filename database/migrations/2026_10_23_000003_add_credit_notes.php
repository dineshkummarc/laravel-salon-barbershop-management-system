<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $t) {
            $t->unsignedBigInteger('credited')->default(0);
            $t->unsignedBigInteger('tax_credited')->default(0);
        });
        Schema::table('sale_items', fn (Blueprint $t) => $t->unsignedBigInteger('commission_reversed')->default(0));
        Schema::table('journals', fn (Blueprint $t) => $t->foreignId('user_id')->nullable()->constrained());
        Schema::table('payments', fn (Blueprint $t) => $t->foreignId('original_payment_id')->nullable()->constrained('payments'));
        Schema::create('credit_notes', function (Blueprint $t) {
            $t->id();
            $t->foreignId('business_id')->constrained();
            $t->foreignId('branch_id')->constrained();
            $t->foreignId('sale_id')->constrained();
            $t->string('idempotency_key')->unique();
            $t->unsignedBigInteger('amount');
            $t->unsignedBigInteger('tax');
            $t->string('reason');
            $t->json('items');
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credit_notes');
        Schema::table('payments', fn (Blueprint $t) => $t->dropConstrainedForeignId('original_payment_id'));
        Schema::table('journals', fn (Blueprint $t) => $t->dropConstrainedForeignId('user_id'));
        Schema::table('sale_items', fn (Blueprint $t) => $t->dropColumn('commission_reversed'));
        Schema::table('sales', fn (Blueprint $t) => $t->dropColumn(['credited', 'tax_credited']));
    }
};
