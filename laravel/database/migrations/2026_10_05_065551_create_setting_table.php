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
        Schema::create('setting', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('title')->nullable();
            $table->string('storename', 100)->nullable();
            $table->text('address')->nullable();
            $table->string('email', 50)->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('logo', 50)->nullable();
            $table->string('splash_logo');
            $table->string('favicon', 100)->nullable();
            $table->integer('vat')->default(0);
            $table->integer('isvatnumshow')->nullable()->default(0);
            $table->string('vattinno', 30)->nullable();
            $table->integer('servicecharge')->default(0);
            $table->integer('discount_type')->default(0)->comment('0=amount,1=percent');
            $table->integer('service_chargeType')->default(0)->comment('0=amount,1=percent');
            $table->decimal('discountrate', 19, 3)->default(0);
            $table->string('country', 100)->nullable();
            $table->string('map_key')->nullable();
            $table->double('latitude')->nullable();
            $table->double('longitude')->nullable();
            $table->integer('currency')->nullable()->default(0);
            $table->string('language', 100)->nullable();
            $table->string('timezone', 150);
            $table->time('checkintime');
            $table->time('checkouttime');
            $table->text('dateformat');
            $table->string('site_align', 50)->nullable();
            $table->text('pricetxt')->nullable();
            $table->text('powerbytxt')->nullable();
            $table->string('footer_text')->nullable();
            $table->integer('top_offer_visible_status')->nullable()->default(0);
            $table->integer('use_web_status')->nullable()->default(0);
            $table->integer('blog_offer_visible_status')->nullable()->default(0);
            $table->integer('home_about_visible_status')->nullable()->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('setting');
    }
};
