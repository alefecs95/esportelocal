<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_notifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('prefeitura_id')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('phone', 20);
            $table->boolean('receive_notifications')->default(true);
            $table->string('unsubscribe_token', 80)->unique();
            $table->timestamp('last_notified_at')->nullable();
            $table->timestamps();

            $table->index(['prefeitura_id', 'receive_notifications']);
            $table->unique(['prefeitura_id', 'phone']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_notifications');
    }
};
