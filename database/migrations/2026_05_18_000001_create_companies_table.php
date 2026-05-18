<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('osm_id')->nullable()->unique();
            $table->string('name');
            $table->decimal('lat', 10, 7);
            $table->decimal('lon', 10, 7);
            $table->string('category', 50)->default('all');
            $table->string('address')->nullable();
            $table->string('source', 20)->default('osm');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};