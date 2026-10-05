<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The old schema stored unsalted MD5 hashes in 32-character columns. Widen them so
     * bcrypt/argon hashes fit; existing MD5 hashes are upgraded on each account's next login.
     */
    public function up(): void
    {
        Schema::table('user', function (Blueprint $table) {
            $table->string('password', 255)->change();
        });

        Schema::table('customerinfo', function (Blueprint $table) {
            $table->string('pass', 255)->nullable()->change();
        });
    }

    public function down(): void
    {
        // Intentionally irreversible: shrinking the columns would truncate modern hashes.
    }
};
