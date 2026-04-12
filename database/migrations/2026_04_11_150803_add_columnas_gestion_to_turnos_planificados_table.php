<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('turnos_planificados', function (Blueprint $table) {
            // Checkbox para saber si cruza la medianoche
            $table->boolean('es_nocturno')->default(false)->after('hora_salida');
            
            // Para diferenciar un turno normal de unas vacaciones o descansos
            $table->string('tipo_registro')->default('Turno de Trabajo')->after('estado'); 
            // Opciones: 'Turno de Trabajo', 'Día Libre', 'Vacaciones', 'Descanso Médico'
        });
    }

    public function down()
    {
        Schema::table('turnos_planificados', function (Blueprint $table) {
            $table->dropColumn(['es_nocturno', 'tipo_registro']);
        });
    }
};