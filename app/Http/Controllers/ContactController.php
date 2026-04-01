<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ContactMessage;
use App\Http\Requests\ContactFormRequest;
use Illuminate\Support\Facades\Mail;
use App\Mail\NewContactAlert;
use Illuminate\Support\Facades\Log;

class ContactController extends Controller
{
    // 🌐 PÚBLICA: Guardar un nuevo mensaje enviado desde la web
    public function store(ContactFormRequest $request)
    {
        $message = ContactMessage::create($request->validated());
        
        try {       
            \Illuminate\Support\Facades\Mail::raw("Tienes un nuevo mensaje de: {$message->sender_name}\n\nMensaje: {$message->message}", function($mail) {
                $mail->to('ericksandrillo5@gmail.com')
                    ->subject('¡Nuevo mensaje en Amazon Nuts!');
            });
        } catch(\Exception $e) {
            Log::error('Fallo al enviar correo a Admin: ' . $e->getMessage());
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

    // 🔐 PRIVADA: Cambiar el estado de un mensaje (Leído, Respondido)
    public function updateStatus(Request $request, $id)
    {
        // 1. Validamos que el estado que nos manda Angular sea válido
        $request->validate([
            'status' => 'required|in:unread,read,replied'
        ]);

        // 2. Buscamos el mensaje
        $message = ContactMessage::findOrFail($id);
        
        // 3. Actualizamos y guardamos
        $message->status = $request->status;
        $message->save();

        return response()->json([
            'message' => 'Estado actualizado correctamente',
            'data' => $message
        ], 200);
    }

    // 🔐 PRIVADA: Enviar respuesta al cliente por correo
    public function reply(Request $request, $id)
    {
        // 1. Validamos que Angular nos envíe un texto
        $request->validate([
            'reply_message' => 'required|string'
        ]);

        // 2. Buscamos el mensaje para saber a qué correo responder
        $message = ContactMessage::findOrFail($id);

        try {
            // 3. Enviamos un correo de texto simple al cliente
            \Illuminate\Support\Facades\Mail::raw($request->reply_message, function($mail) use ($message) {
                $mail->to($message->email)
                     ->subject('Respuesta a su consulta - Amazon Nuts');
            });

            // 4. Cambiamos el estado automáticamente a "Respondido"
            $message->status = 'replied';
            $message->save();

            return response()->json([
                'message' => 'Respuesta enviada correctamente',
                'data' => $message
            ], 200);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error al responder: ' . $e->getMessage());
            return response()->json(['message' => 'Error al enviar el correo'], 500);
        }
    }

    // 🗑️ PRIVADA: Eliminar un mensaje (SoftDelete o permanente)
    public function destroy($id)
    {
        try {
            $message = ContactMessage::findOrFail($id);
            $message->delete(); // Si tienes SoftDeletes, esto lo manda a la papelera oculta.

            return response()->json([
                'message' => 'Mensaje eliminado correctamente'
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al eliminar el mensaje'], 500);
        }
    }
}