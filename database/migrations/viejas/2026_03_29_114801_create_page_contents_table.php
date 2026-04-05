<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('page_contents', function (Blueprint $table) {
            $table->id();
            // Clave foránea que conecta con la tabla pages
            $table->foreignId('page_id')->constrained('pages')->onDelete('cascade');
            
            $table->string('section_key', 100); // Ej: 'hero_title'
            $table->enum('content_type', ['text', 'html', 'image_url'])->default('text');
            $table->text('content_value')->nullable();
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('page_contents');
    }
};