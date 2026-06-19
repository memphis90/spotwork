<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_evaluations', function (Blueprint $table) {
            $table->unique(['portal_job_id', 'user_career_profile_id'], 'job_evaluations_job_profile_unique');
        });
    }

    public function down(): void
    {
        Schema::table('job_evaluations', function (Blueprint $table) {
            // MariaDB uses the composite index as FK backing; drop FKs first
            $table->dropForeign(['portal_job_id']);
            $table->dropForeign(['user_career_profile_id']);
            $table->dropUnique('job_evaluations_job_profile_unique');
            $table->foreign('portal_job_id')->references('id')->on('portal_jobs')->cascadeOnDelete();
            $table->foreign('user_career_profile_id')->references('id')->on('user_career_profiles')->cascadeOnDelete();
        });
    }
};
