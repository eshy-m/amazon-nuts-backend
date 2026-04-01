<?php

use Illuminate\Support\Facades\Route;

Route::get('/test-bd', function () {
    try {
        // Limpiamos caché fuertemente
        \Illuminate\Support\Facades\Artisan::call('config:clear');
        \Illuminate\Support\Facades\Artisan::call('cache:clear');
        
        // Obtenemos a la fuerza los datos de conexión actuales
        $conexion = \Illuminate\Support\Facades\DB::connection()->getPdo();
        $driver = \Illuminate\Support\Facades\DB::connection()->getDriverName();
        $bd = \Illuminate\Support\Facades\DB::connection()->getDatabaseName();
        
        return "✅ ¡Conectado exitosamente! Motor: $driver | Base de datos: $bd";
    } catch (\Exception $e) {
        return "❌ Error fatal al conectar: " . $e->getMessage();
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
