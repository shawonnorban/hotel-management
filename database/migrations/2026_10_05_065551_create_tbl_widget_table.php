<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tbl_widget', function (Blueprint $table) {
            $table->integer('widgetid', true);
            $table->string('widget_name', 100);
            $table->string('widget_title', 150)->nullable();
            $table->text('widget_desc')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_widget');
    }
};
