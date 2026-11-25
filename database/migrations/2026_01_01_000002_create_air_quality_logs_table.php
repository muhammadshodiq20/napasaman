<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('air_quality_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('region_id')->constrained('regions')->cascadeOnDelete();
            $table->decimal('pm25', 8, 2)->default(0);
            $table->decimal('pm10', 8, 2)->default(0);
            $table->decimal('no2', 8, 2)->default(0);
            $table->unsignedInteger('aqi')->default(0);
            $table->string('category', 64);
            $table->string('source', 64)->default('open-meteo');
            $table->timestamp('recorded_at');
            $table->timestamps();

            $table->index(['region_id', 'recorded_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('air_quality_logs');
    }
};
