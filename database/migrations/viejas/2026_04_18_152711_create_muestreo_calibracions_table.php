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
    Schema::create('muestreos_calibraciones', function (Blueprint $table) {
        $table->id();
        $table->foreignId('lote_id')->constrained('lotes_producciones')->onDelete('cascade');
        $table->decimal('peso_muestra', 8, 2)->comment('Ej. 8kg (Peso total recolectado en 2 min)');
        $table->decimal('peso_entera', 8, 2);
        $table->decimal('peso_partida', 8, 2);
        $table->decimal('peso_ojos', 8, 2);
        $table->decimal('peso_podrido', 8, 2);
        $table->decimal('porcentaje_partida', 5, 2)->comment('Se usa para la alerta del 13%');
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('muestreo_calibracions');
    }
};
