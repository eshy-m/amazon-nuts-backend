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
            // 🔥 TRUCO API: Usamos HTTP en lugar de SMTP para burlar el bloqueo de Railway
            $response = Http::withToken(env('RESEND_API_KEY'))
                ->post('https://api.resend.com/emails', [
                    'from' => 'onboarding@resend.dev', // Resend exige usar este correo de prueba al inicio
                    'to' => 'ericksandrillo5@gmail.com', // A dónde llegará
                    'subject' => '¡Nuevo mensaje de ' . $message->sender_name . '!',
                    'text' => "Tienes un nuevo mensaje de: {$message->sender_name}\n\nCorreo: {$message->email}\nEmpresa: {$message->company_name}\n\nMensaje: {$message->message}"
                ]);

            if (!$response->successful()) {
                Log::error('Error de Resend: ' . $response->body());
            }

        } catch(\Throwable $e) { 
            Log::error('Fallo crítico al enviar API: ' . $e->getMessage());
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