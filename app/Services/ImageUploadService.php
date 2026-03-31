<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ImageUploadService
{
    /**
     * Sube una imagen y devuelve su URL pública.
     */
    public function uploadPageImage(UploadedFile $file): string//ESPERA RECIBIR UN ARCHIVO SUBIDO
    {
        // 1. Generamos un nombre único para que no se sobreescriban imágenes con el mismo nombre
        $fileName = time() . '_' . preg_replace('/[^a-zA-Z0-9_.-]/', '_', $file->getClientOriginalName());
        //$fileName es una variable que guarda el nombre del archivo final
        // 2. Guardamos la imagen en la carpeta storage/app/public/pages
        $path = $file->storeAs('pages', $fileName, 'public');
        //la variable $path es una variable que guarda la ruta de la imagen en este caso
        //storage/app/public/pages/"nombre_del_archivo.jpg"
        // 3. Devolvemos la ruta relativa de la imagen
        return Storage::url($path);
        //Storage Convierte la ruta interna en una URL accesible, por ejemplo:/storage/pages/imagen.jpg
    }
}