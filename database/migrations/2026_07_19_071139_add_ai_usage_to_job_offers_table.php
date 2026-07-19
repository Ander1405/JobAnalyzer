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
        Schema::table('job_offers', function (Blueprint $table) {
            $table->string('ai_model')->nullable()->after('ai_provider');
            $table->unsignedInteger('ai_duration_ms')->nullable()->after('ai_model');
            $table->unsignedInteger('ai_input_tokens')->nullable()->after('ai_duration_ms');
            $table->unsignedInteger('ai_output_tokens')->nullable()->after('ai_input_tokens');
            $table->decimal('ai_cost_usd', 10, 6)->nullable()->after('ai_output_tokens');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('job_offers', function (Blueprint $table) {
            $table->dropColumn(['ai_model', 'ai_duration_ms', 'ai_input_tokens', 'ai_output_tokens', 'ai_cost_usd']);
        });
    }
};
