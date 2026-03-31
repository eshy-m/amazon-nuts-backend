<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Page;
use App\Models\PageContent;
use App\Services\ImageUploadService;

class PageContentController extends Controller
{
    // 1. PÚBLICA: Obtener todo el contenido de una página por su nombre (slug)
    // Ejemplo: /api/public/pages/inicio
    public function getPageBySlug($slug)
    {
        // Buscamos la página y traemos también todos sus contenidos ("with('contents')")
        $page = Page::with('contents')->where('slug', $slug)->first();

        if (!$page) {
            return response()->json(['message' => 'Página no encontrada'], 404);
        }

        return response()->json($page, 200);
    }

    // 2. PRIVADA: Actualizar un texto o HTML específico
    // Ejemplo: El admin cambia el título principal de la web
    public function update(Request $request, $id)
    {
        $request->validate([
            'content_value' => 'required'
        ]);

        $content = PageContent::find($id);

        if (!$content) {
            return response()->json(['message' => 'Contenido no encontrado'], 404);
        }

        // Actualizamos el valor
        $content->content_value = $request->content_value;
        $content->save();

        return response()->json([
            'message' => 'Contenido actualizado correctamente',
            'data' => $content
        ], 200);
    }

    // 3. PRIVADA: Subir una imagen (Ej: Foto de la planta procesadora)
    // 🔐 3. PRIVADA: Subir una imagen inyectando el Servicio
    public function uploadImage(Request $request, ImageUploadService $imageService)
    {
        // 1. Validamos que venga una imagen real y que el contenido exista
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,webp|max:2048', // Max 2MB
            'content_id' => 'required|exists:page_contents,id'
        ]);

        // 2. Usamos el servicio para guardar el archivo físico en el servidor
        $imageUrl = $imageService->uploadPageImage($request->file('image'));

        // 3. Actualizamos la base de datos con la nueva URL
        $content = PageContent::find($request->content_id);
        $content->content_value = $imageUrl;
        $content->content_type = 'image_url'; // Aseguramos que el tipo cambie a imagen
        $content->save();
        
        return response()->json([
            'message' => 'Imagen subida y guardada correctamente.',
            'url' => url($imageUrl), // url() genera la ruta completa: http://127.0.0.1:8000/storage/pages/...
            'data' => $content
        ], 200);
    }
}