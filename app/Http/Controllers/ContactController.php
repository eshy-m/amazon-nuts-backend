<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ContactMessage;
use App\Http\Requests\ContactFormRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ContactController extends Controller
{
    // 🌐 PÚBLICA: Guardar un nuevo mensaje enviado desde la web
    public function store(ContactFormRequest $request)
    {
        $message = ContactMessage::create($request->validated());
        
        try {       
            // 🔥 TRUCO API: Usamos HTTP + withoutVerifying() para burlar a Railway
            $response = Http::withoutVerifying()
                ->withToken(env('RESEND_API_KEY'))
                ->post('https://api.resend.com/emails', [
                    'from' => 'onboarding@resend.dev', // Remitente obligatorio de prueba
                    'to' => 'ericksandrillo5@gmail.com', // A dónde llegará la alerta
                    'subject' => '¡Nuevo mensaje de ' . $message->sender_name . '!',
                    'text' => "Tienes un nuevo mensaje en Amazon Nuts:\n\nNombre: {$message->sender_name}\nCorreo: {$message->email}\nEmpresa: {$message->company_name}\n\nMensaje:\n{$message->message}"
                ]);

            if (!$response->successful()) {
                Log::error('Error de Resend al guardar: ' . $response->body());
            }

        } catch(\Throwable $e) { 
            Log::error('Fallo crítico al enviar API en store: ' . $e->getMessage());
        }

        return response()->json([
            'message' => '¡Gracias por contactar a Amazon Nuts! Tu mensaje ha sido enviado con éxito.',
            'data' => $message
        ], 201);
    }

    // 🔐 PRIVADA: Obtener todos los mensajes para el Panel de Admin
    public function index()
    {
        $messages = ContactMessage::orderBy('created_at', 'desc')->get();
        return response()->json($messages, 200);
    }

    // 🔐 PRIVADA: Cambiar el estado de un mensaje
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:unread,read,replied'
        ]);

        $message = ContactMessage::findOrFail($id);
        $message->status = $request->status;
        $message->save();

        return response()->json([
            'message' => 'Estado actualizado correctamente',
            'data' => $message
        ], 200);
    }

    // 🔐 PRIVADA: Enviar respuesta al cliente (MODO SIMULADOR ANTIBLOQUEO)
    public function reply(Request $request, $id)
    {
        $request->validate([
            'reply_message' => 'required|string'
        ]);

        $message = ContactMessage::findOrFail($id);

        try {
            // 🔥 TRUCO API: HTTP + withoutVerifying()
            $response = Http::withoutVerifying()
                ->withToken(env('RESEND_API_KEY'))
                ->post('https://api.resend.com/emails', [
                    'from' => 'onboarding@resend.dev',
                    'to' => 'ericksandrillo5@gmail.com', // Simulador: llega a tu correo
                    'subject' => 'Simulador de Respuesta - Amazon Nuts',
                    'html' => "
                        <h3>🚨 Prueba del Sistema de Respuestas</h3>
                        <p><b>Originalmente este mensaje iba a ser enviado al cliente:</b> {$message->email}</p>
                        <hr>
                        <p><b>El cliente preguntó:</b> <br> {$message->message}</p>
                        <br>
                        <p><b>Tu respuesta desde el panel Admin fue:</b> <br> {$request->reply_message}</p>
                    "
                ]);

            if (!$response->successful()) {
                return response()->json(['message' => 'Error de Resend: ' . $response->body()], 500);
            }

            $message->status = 'replied';
            $message->save();

            return response()->json([
                'message' => '¡Éxito! Respuesta simulada enviada a tu correo.',
                'data' => $message
            ], 200);

        } catch (\Throwable $e) {
            Log::error('Error al responder: ' . $e->getMessage());
            return response()->json(['message' => 'Error de API: ' . $e->getMessage()], 500);
        }
    }

    // 🗑️ PRIVADA: Eliminar un mensaje
    public function destroy($id)
    {
        try {
            $message = ContactMessage::findOrFail($id);
            $message->delete(); 

            return response()->json([
                'message' => 'Mensaje eliminado correctamente'
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al eliminar el mensaje'], 500);
        }
    }
}