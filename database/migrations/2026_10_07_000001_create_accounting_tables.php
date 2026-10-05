<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Double-entry ledger. The old acc_* tables are left untouched (and unused): they keyed accounts by
     * name, had no balancing rules and no periods, so a clean ledger is created instead.
     */
    public function up(): void
    {
        Schema::create('ledger_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name', 150);
            $table->enum('type', ['asset', 'liability', 'equity', 'income', 'expense']);
            $table->foreignId('parent_id')->nullable()->constrained('ledger_accounts')->restrictOnDelete();
            $table->boolean('is_group')->default(false);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_cash')->default(false);
            $table->string('system_key', 40)->nullable()->unique();
            $table->decimal('opening_balance', 14, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('financial_years', function (Blueprint $table) {
            $table->id();
            $table->string('title', 60);
            $table->date('start_date');
            $table->date('end_date');
            $table->boolean('is_closed')->default(false);
            $table->timestamps();
        });

        Schema::create('journal_entries', function (Blueprint $table) {
            $table->id();
            $table->string('number', 30)->unique();
            $table->date('entry_date')->index();
            $table->string('type', 20)->index(); // journal, receipt, payment, contra, booking, purchase, payroll, adjustment
            $table->text('narration')->nullable();
            $table->nullableMorphs('source');
            $table->unsignedInteger('created_by')->nullable();
            $table->enum('status', ['posted', 'void'])->default('posted')->index();
            $table->foreignId('reversal_of')->nullable()->constrained('journal_entries')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('journal_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('journal_entry_id')->constrained('journal_entries')->cascadeOnDelete();
            $table->foreignId('ledger_account_id')->constrained('ledger_accounts')->restrictOnDelete();
            $table->decimal('debit', 14, 2)->default(0);
            $table->decimal('credit', 14, 2)->default(0);
            $table->string('memo')->nullable();
            $table->index(['ledger_account_id', 'journal_entry_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journal_lines');
        Schema::dropIfExists('journal_entries');
        Schema::dropIfExists('financial_years');
        Schema::dropIfExists('ledger_accounts');
    }
};
