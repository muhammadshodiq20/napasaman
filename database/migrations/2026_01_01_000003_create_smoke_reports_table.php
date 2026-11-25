<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('smoke_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('region_id')->constrained('regions')->cascadeOnDelete();
            $table->string('reporter_name');
            $table->string('reporter_phone', 32)->nullable();
            $table->string('category', 80);
            $table->text('description');
            $table->decimal('latitude', 10, 6);
            $table->decimal('longitude', 10, 6);
            $table->string('photo_path')->nullable();
            $table->enum('status', ['dilaporkan', 'diverifikasi', 'ditindaklanjuti'])->default('dilaporkan');
            $table->text('admin_notes')->nullable();
            $table->timestamps();

            $table->index(['region_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('smoke_reports');
    }
};
