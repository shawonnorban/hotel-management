<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hk_checklist_items', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->unsignedSmallInteger('sort')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('hk_tasks', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('room_assign_id')->index(); // tbl_roomnofloorassign.roomassignid
            $table->foreignId('assigned_to')->nullable()->constrained('hr_employees')->nullOnDelete();
            $table->date('task_date')->index();
            $table->enum('status', ['pending', 'in_progress', 'done', 'inspected', 'cancelled'])->default('pending')->index();
            $table->string('source', 20)->default('staff'); // staff | qr
            $table->text('notes')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->timestamps();
        });

        Schema::create('hk_task_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained('hk_tasks')->cascadeOnDelete();
            $table->string('name', 150);
            $table->boolean('is_done')->default(false);
        });

        Schema::create('hk_laundry_products', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120)->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('hk_laundry_costs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('hk_laundry_products')->cascadeOnDelete();
            $table->string('service', 30);
            $table->decimal('cost', 12, 2);
            $table->timestamps();
            $table->unique(['product_id', 'service']);
        });

        Schema::create('hk_laundry_orders', function (Blueprint $table) {
            $table->id();
            $table->string('number', 30)->unique();
            $table->date('order_date')->index();
            $table->unsignedInteger('booking_id')->nullable()->index();
            $table->string('guest_name', 150);
            $table->string('room_no', 20)->nullable();
            $table->enum('status', ['received', 'washing', 'ready', 'delivered', 'cancelled'])->default('received');
            $table->decimal('total', 12, 2)->default(0);
            $table->decimal('paid', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->timestamps();
        });

        Schema::create('hk_laundry_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('hk_laundry_orders')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('hk_laundry_products')->restrictOnDelete();
            $table->string('service', 30);
            $table->unsignedInteger('quantity');
            $table->decimal('unit_cost', 12, 2);
            $table->decimal('line_total', 12, 2);
        });

        Schema::create('hk_laundry_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('hk_laundry_orders')->cascadeOnDelete();
            $table->date('paid_on')->index();
            $table->decimal('amount', 12, 2);
            $table->foreignId('ledger_account_id')->constrained('ledger_accounts')->restrictOnDelete();
            $table->string('reference', 80)->nullable();
            $table->unsignedBigInteger('journal_entry_id')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['hk_laundry_payments', 'hk_laundry_lines', 'hk_laundry_orders', 'hk_laundry_costs', 'hk_laundry_products', 'hk_task_items', 'hk_tasks', 'hk_checklist_items'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
