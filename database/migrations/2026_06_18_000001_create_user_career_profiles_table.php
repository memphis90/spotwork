<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_career_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->json('target_roles')->nullable();
            $table->json('skills')->nullable();
            $table->unsignedInteger('min_salary')->nullable();
            $table->json('preferred_locations')->nullable();
            $table->enum('scan_frequency', ['daily', 'weekly'])->default('weekly');
            $table->timestamp('next_scan_at')->nullable();
            $table->unsignedTinyInteger('map_score_threshold')->default(60);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_career_profiles');
    }
};
