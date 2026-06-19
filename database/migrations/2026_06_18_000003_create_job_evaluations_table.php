<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('portal_job_id')->constrained('portal_jobs')->cascadeOnDelete();
            $table->foreignId('user_career_profile_id')->constrained('user_career_profiles')->cascadeOnDelete();
            $table->unsignedTinyInteger('score');
            $table->json('score_breakdown')->nullable();
            $table->timestamp('evaluated_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_evaluations');
    }
};
