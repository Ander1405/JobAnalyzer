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
        Schema::create('tracked_jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_id')->unique()->constrained('job_offers')->cascadeOnDelete();
            $table->string('status')->default('sin_aplicar');
            $table->string('priority')->nullable();
            $table->dateTime('applied_at')->nullable();
            $table->string('cv_version_used')->nullable();
            $table->string('next_action')->nullable();
            $table->date('next_action_date')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tracked_jobs');
    }
};
