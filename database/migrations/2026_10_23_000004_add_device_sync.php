<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_devices', function (Blueprint $t) {
            $t->id();
            $t->foreignId('business_id')->constrained();
            $t->foreignId('branch_id')->constrained();
            $t->string('name');
            $t->string('provider')->default('fake');
            $t->string('host')->nullable();
            $t->unsignedInteger('port')->default(4370);
            $t->text('credentials')->nullable();
            $t->json('employee_map');
            $t->string('status')->default('not_synced');
            $t->dateTime('last_sync_at')->nullable();
            $t->timestamps();
        });
        Schema::create('attendance_events', function (Blueprint $t) {
            $t->id();
            $t->foreignId('business_id')->constrained();
            $t->foreignId('branch_id')->constrained();
            $t->foreignId('attendance_device_id')->constrained();
            $t->foreignId('employee_id')->nullable()->constrained();
            $t->string('device_user_id');
            $t->string('event_hash')->unique();
            $t->dateTime('punched_at');
            $t->json('payload');
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_events');
        Schema::dropIfExists('attendance_devices');
    }
};
