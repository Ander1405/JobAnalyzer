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
            $table->string('apply_url')->nullable()->after('url');
            $table->string('location')->nullable()->after('apply_url');
            $table->boolean('is_remote')->nullable()->after('location');
            $table->string('work_mode')->nullable()->after('is_remote');
            $table->string('seniority')->nullable()->after('work_mode');
            $table->string('employment_type')->nullable()->after('seniority');
            $table->dateTime('posted_at')->nullable()->after('employment_type');
            $table->dateTime('expires_at')->nullable()->after('posted_at');
            $table->string('company_logo')->nullable()->after('expires_at');
            $table->string('company_website')->nullable()->after('company_logo');
            $table->json('benefits')->nullable()->after('company_website');
            $table->json('required_skills')->nullable()->after('benefits');
            $table->unsignedInteger('applicants_count')->nullable()->after('required_skills');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('job_offers', function (Blueprint $table) {
            $table->dropColumn([
                'apply_url',
                'location',
                'is_remote',
                'work_mode',
                'seniority',
                'employment_type',
                'posted_at',
                'expires_at',
                'company_logo',
                'company_website',
                'benefits',
                'required_skills',
                'applicants_count',
            ]);
        });
    }
};
