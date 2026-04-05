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
            // Relación: Esta asistencia pertenece a un trabajador
            $table->foreignId('trabajador_id')->constrained('trabajadores')->onDelete('cascade');
            
            $table->date('fecha'); // Ej: 2026-04-01
            $table->time('hora_entrada'); // Ej: 08:00:00
            $table->time('hora_salida')->nullable(); // Nulo al inicio del día
            
            // Guardaremos las horas calculadas. Ej: 8.50 (8 horas y media)
            $table->decimal('horas_trabajadas', 5, 2)->nullable(); 
            
            $table->string('observacion')->nullable(); // Por si llegó tarde, permiso, etc.
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asistencias');
    }
};