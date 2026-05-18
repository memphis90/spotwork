<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('saved_jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('job_url', 500);
            $table->string('job_title');
            $table->timestamp('saved_at')->useCurrent();
            $table->unique(['user_id', 'job_url']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saved_jobs');
    }
};