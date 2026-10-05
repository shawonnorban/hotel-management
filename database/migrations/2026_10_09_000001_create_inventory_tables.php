<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Purchasing and stock. The old products/purchase tables kept whole-number stock and had no costing, so these replace them. */
    public function up(): void
    {
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->string('name', 150);
            $table->string('contact_person', 120)->nullable();
            $table->string('email', 150)->nullable();
            $table->string('phone', 40)->nullable();
            $table->string('address', 255)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('item_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120)->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->string('name', 80)->unique();
            $table->string('short_code', 12);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('inventory_items', function (Blueprint $table) {
            $table->id();
            $table->string('sku', 40)->unique();
            $table->string('name', 160);
            $table->foreignId('category_id')->nullable()->constrained('item_categories')->restrictOnDelete();
            $table->foreignId('unit_id')->constrained('units')->restrictOnDelete();
            $table->decimal('stock', 14, 3)->default(0);
            $table->decimal('avg_cost', 14, 4)->default(0);
            $table->decimal('reorder_level', 14, 3)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('purchases', function (Blueprint $table) {
            $table->id();
            $table->string('number', 30)->unique();
            $table->foreignId('supplier_id')->constrained('suppliers')->restrictOnDelete();
            $table->date('purchase_date')->index();
            $table->string('reference', 80)->nullable(); // supplier's invoice number
            $table->decimal('subtotal', 14, 2);
            $table->decimal('discount', 14, 2)->default(0);
            $table->decimal('total', 14, 2);
            $table->decimal('paid', 14, 2)->default(0);
            $table->enum('status', ['received', 'void'])->default('received');
            $table->text('notes')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->unsignedBigInteger('journal_entry_id')->nullable();
            $table->timestamps();
        });

        Schema::create('purchase_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_id')->constrained('purchases')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('inventory_items')->restrictOnDelete();
            $table->decimal('quantity', 14, 3);
            $table->decimal('returned', 14, 3)->default(0);
            $table->decimal('unit_cost', 14, 4);
            $table->decimal('line_total', 14, 2);
        });

        Schema::create('purchase_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_id')->constrained('purchases')->cascadeOnDelete();
            $table->date('paid_on');
            $table->decimal('amount', 14, 2);
            $table->foreignId('ledger_account_id')->constrained('ledger_accounts')->restrictOnDelete();
            $table->string('reference', 80)->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->unsignedBigInteger('journal_entry_id')->nullable();
            $table->timestamps();
        });

        Schema::create('purchase_returns', function (Blueprint $table) {
            $table->id();
            $table->string('number', 30)->unique();
            $table->foreignId('purchase_id')->constrained('purchases')->restrictOnDelete();
            $table->date('return_date');
            $table->decimal('total', 14, 2);
            $table->string('reason', 250)->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->unsignedBigInteger('journal_entry_id')->nullable();
            $table->timestamps();
        });

        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained('inventory_items')->cascadeOnDelete();
            $table->enum('type', ['purchase', 'return', 'issue', 'waste', 'adjustment']);
            $table->decimal('quantity', 14, 3); // signed: + in, − out
            $table->decimal('unit_cost', 14, 4)->default(0);
            $table->decimal('balance', 14, 3); // stock after the movement
            $table->nullableMorphs('source');
            $table->string('note', 255)->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->timestamp('moved_at')->useCurrent()->index();
            $table->index(['item_id', 'id']);
        });
    }

    public function down(): void
    {
        foreach (['stock_movements', 'purchase_returns', 'purchase_payments', 'purchase_items', 'purchases', 'inventory_items', 'units', 'item_categories', 'suppliers'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
