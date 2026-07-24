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
        Schema::create('tracked_job_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tracked_job_id')->constrained('tracked_jobs')->cascadeOnDelete();
            $table->text('body');
            $table->string('type')->default('nota');
            $table->timestamp('created_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tracked_job_comments');
    }
};
