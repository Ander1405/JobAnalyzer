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
        Schema::create('profiles', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('label');
            $table->json('contact')->nullable();
            $table->string('headline')->nullable();
            $table->text('summary')->nullable();
            $table->json('experience')->nullable();
            $table->json('skills')->nullable();
            $table->json('education')->nullable();
            $table->json('languages')->nullable();
            $table->json('certifications')->nullable();
            $table->longText('raw_md');
            $table->boolean('is_active')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('profiles');
    }
};
