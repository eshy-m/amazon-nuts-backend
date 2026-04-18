<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
   public function up()
{
    Schema::create('pesajes_selecciones', function (Blueprint $table) {
        $table->id();
        $table->foreignId('lote_id')->constrained('lotes_producciones')->onDelete('cascade');
        $table->enum('categoria', ['Primera', 'Partida', 'Ojos']);
        $table->decimal('peso', 8, 2);
        $table->time('hora_registro');
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pesaje_seleccions');
    }
};
