<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('audits', function (Blueprint $table) {
            $table->json('error_context')->nullable()->after('error_message');
            $table->timestamp('webhook_delivered_at')->nullable()->after('completed_at');
            $table->unsignedSmallInteger('webhook_status')->nullable()->after('webhook_delivered_at');
            $table->unsignedTinyInteger('webhook_attempts')->default(0)->after('webhook_status');
            $table->unsignedBigInteger('created_by_token_id')->nullable()->after('lang');
            $table->string('created_by_ip', 45)->nullable()->after('created_by_token_id');
            $table->text('user_agent')->nullable()->after('created_by_ip');
        });
    }

    public function down(): void
    {
        //
    }
};
