<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Creamos el Rol de Administrador y guardamos su ID
        $adminRoleId = DB::table('roles')->insertGetId([
            'name' => 'Administrador',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 2. Creamos tu primer Usuario Administrador
        DB::table('users')->insert([
            'role_id' => $adminRoleId,
            'name' => 'Admin Amazon Nuts',
            'email' => 'admin@amazonnuts.com',
            // Hash::make() encripta la contraseña por seguridad
            'password' => Hash::make('admin123'), 
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 3. Creamos las páginas base de tu MVP para que el Frontend las detecte
        DB::table('pages')->insert([
            ['name' => 'Inicio', 'slug' => 'inicio', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Nosotros', 'slug' => 'nosotros', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Proceso', 'slug' => 'proceso', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Productos', 'slug' => 'productos', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}