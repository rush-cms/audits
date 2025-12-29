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
        Schema::table('audits', function (Blueprint $table) {
            $table->json('pagespeed_data')->nullable()->after('error_message');
            $table->json('screenshots_data')->nullable()->after('pagespeed_data');
            $table->json('processing_steps')->nullable()->after('screenshots_data');
            $table->timestamp('last_attempt_at')->nullable()->after('processing_steps');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
