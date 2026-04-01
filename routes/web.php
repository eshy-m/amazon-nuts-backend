<?php

use Illuminate\Support\Facades\Route;

Route::get('/fuerza-bruta-mysql', function () {
    try {
        // 1. Forzamos a Laravel a usar la configuración de MySQL en este exacto momento
        config(['database.default' => 'mysql']);
        config(['database.connections.mysql.host' => env('DB_HOST', 'mysql.railway.internal')]);
        config(['database.connections.mysql.port' => env('DB_PORT', '3306')]);
        config(['database.connections.mysql.database' => env('DB_DATABASE', 'railway')]);
        config(['database.connections.mysql.username' => env('DB_USERNAME', 'root')]);
        config(['database.connections.mysql.password' => env('DB_PASSWORD')]);

        // 2. Desconectamos cualquier rastro de SQLite y conectamos el MySQL puro
        \Illuminate\Support\Facades\DB::purge('mysql');
        \Illuminate\Support\Facades\DB::reconnect('mysql');

        // 3. Ejecutamos la creación de tablas
        \Illuminate\Support\Facades\Artisan::call('migrate:fresh', ['--force' => true]);
        
        // 4. (Opcional) Si tienes un archivo Seeder con datos de prueba, se ejecuta aquí:
        \Illuminate\Support\Facades\Artisan::call('db:seed', ['--force' => true]);

        return "✅ ¡Victoria! Base de datos conectada a MySQL y tablas creadas a la fuerza.";
    } catch (\Exception $e) {
        return "❌ Error fatal al intentar inyectar MySQL: " . $e->getMessage();
    }
});
// Route::get('/instalar-bd', function () {
//     \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
//     return '¡Tablas creadas con éxito, eres un hacker!';
// });

//como crear usuario y contraseña
// Route::get('/crear-admin-seguro', function () {
//     \App\Models\User::updateOrCreate(
//         ['email' => 'admin@amazonnuts.com'],
//         [
//             'name' => 'Administrador',
//             'password' => \Illuminate\Support\Facades\Hash::make('tu_contraseña_aqui')
//         ]
//     );
//     return 'Usuario admin@amazonnuts.com creado o actualizado con éxito.';
// });
