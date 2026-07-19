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
        Schema::create('job_offers', function (Blueprint $table) {
            $table->id();
            $table->string('hash')->unique();
            $table->string('source');
            $table->string('company');
            $table->string('title');
            $table->text('description');
            $table->string('url');
            $table->string('contract_type')->nullable();
            $table->string('salary_raw')->nullable();
            $table->string('language')->nullable();
            $table->string('status')->default('fetched');
            $table->string('ai_provider')->nullable();
            $table->json('ai_analysis')->nullable();
            $table->string('notion_page_id')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_offers');
    }
};
