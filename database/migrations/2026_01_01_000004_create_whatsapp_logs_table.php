<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_logs', function (Blueprint $table) {
            $table->id();
            $table->string('sender_phone', 40);
            $table->text('incoming_message');
            $table->text('bot_reply');
            $table->string('command', 40)->default('HELP');
            $table->timestamps();

            $table->index(['command', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_logs');
    }
};
