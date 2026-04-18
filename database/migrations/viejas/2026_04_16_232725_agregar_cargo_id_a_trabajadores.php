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
    Schema::table('trabajadores', function (Blueprint $table) {
        // Lo creamos como 'nullable' para que no dé error con los trabajadores que ya existen
        $table->unsignedBigInteger('cargo_id')->nullable()->after('id');
        
        // Creamos la relación. Si un cargo se borra, el trabajador no se borra, solo su cargo_id queda en NULL
        $table->foreign('cargo_id')->references('id')->on('cargos')->onDelete('set null');
    });
}

public function down()
{
    Schema::table('trabajadores', function (Blueprint $table) {
        $table->dropForeign(['cargo_id']);
        $table->dropColumn('cargo_id');
    });
}


};
