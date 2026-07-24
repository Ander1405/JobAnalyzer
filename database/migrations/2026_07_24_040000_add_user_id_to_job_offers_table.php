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
            $table->dropUnique(['hash']);
            $table->foreignId('user_id')->after('id')->constrained()->cascadeOnDelete();
            $table->unique(['hash', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('job_offers', function (Blueprint $table) {
            $table->dropUnique(['hash', 'user_id']);
            $table->dropConstrainedForeignId('user_id');
            $table->unique('hash');
        });
    }
};
