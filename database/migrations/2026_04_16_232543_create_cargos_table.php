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
    Schema::create('cargos', function (Blueprint $table) {
        $table->id();
        $table->string('nombre')->unique(); // Ej: Operario, Seleccionadora
        $table->string('descripcion')->nullable();
        $table->boolean('estado')->default(true); // Para activar o desactivar cargos en el futuro
        $table->timestamps();
    });
}
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cargos');
    }
};
