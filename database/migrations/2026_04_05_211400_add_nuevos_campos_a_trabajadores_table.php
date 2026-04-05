<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trabajadores', function (Blueprint $table) {
            // Agregamos las nuevas columnas
            $table->string('area')->nullable()->after('dni');
            $table->string('celular', 15)->nullable()->after('area');
            $table->string('direccion')->nullable()->after('celular');
            $table->boolean('experiencia')->default(false)->after('direccion');
            $table->text('observaciones')->nullable()->after('experiencia');
            $table->date('fecha_inicio')->nullable()->after('observaciones');
        });
    }

    public function down(): void
    {
        Schema::table('trabajadores', function (Blueprint $table) {
            // Si nos arrepentimos, esto borra solo las columnas nuevas
            $table->dropColumn([
                'area', 
                'celular', 
                'direccion', 
                'experiencia', 
                'observaciones', 
                'fecha_inicio'
            ]);
        });
    }
};
