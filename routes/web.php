<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;

Route::get('/test-correo', function () {
    try {
        \Illuminate\Support\Facades\Mail::raw('Prueba de conexión con Gmail desde Railway', function($mail) {
            $mail->to('ericksandrillo5@gmail.com')
                 ->subject('Prueba Técnica - Amazon Nuts');
        });
        return "✅ CORREO ENVIADO. ¡La conexión con Gmail funciona perfectamente!";
    } catch (\Throwable $e) {
        return "🚨 ERROR EXACTO DE GMAIL: " . $e->getMessage();
    }
});