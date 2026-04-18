<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asistencias', function (Blueprint $table) {
            $table->id();
            // Conectamos la asistencia con el trabajador
            $table->foreignId('trabajador_id')->constrained('trabajadores')->onDelete('cascade');
            
            $table->date('fecha');
            $table->time('hora_entrada')->nullable();
            $table->time('hora_salida')->nullable();
            
            // Área asignada ESE DÍA (Destajo, Selección, etc.)
            $table->string('area_trabajo')->nullable(); 
            
            // Estado (Para saber si es 'A' Asistencia, 'F' Falta, 'P' Permiso)
            $table->string('estado')->default('Asistió'); 
            
            // Columna de observaciones (Igual que en tu hojA física)
            $table->string('observaciones')->nullable(); 
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asistencias');
    }
};