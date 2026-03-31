<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contact_messages', function (Blueprint $table) {
            $table->id();
            // Datos del Cliente B2B
            $table->string('sender_name');
            $table->string('company'); // ¡NUEVO!
            $table->string('email');
            $table->string('country'); // ¡NUEVO!
            $table->string('product_interest'); // ¡NUEVO! (whole, chipped, broken)
            
            // El requerimiento
            $table->text('message');
            $table->enum('status', ['unread', 'read', 'replied'])->default('unread');
            
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_messages');
    }
};