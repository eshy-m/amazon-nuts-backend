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
    Schema::create('lotes_producciones', function (Blueprint $table) {
        $table->id();
        $table->date('fecha');
        $table->integer('cantidad_sacos');
        $table->decimal('peso_por_saco', 8, 2)->default(52.00); // 52 kilos por defecto
        $table->decimal('peso_total_ingreso', 10, 2); // cantidad_sacos * peso_por_saco
       // $table->tinyInteger('tamano_castana')->comment('1=Grande, 2=Mediano, 3=Pequeño');
        $table->string('estado')->default('En Proceso')->comment('En Proceso, Finalizado');
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lote_produccions');
    }
};
