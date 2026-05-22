<?php

namespace App\Tags;

use Statamic\Tags\Tags;
use App\Models\Analysis;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str; // Importamos la clase Str

class HairAnalysis extends Tags
{
    public function index()
    {
        $slug = $this->context->get('slug');
        $userId = session('usuario_id');

        $analysis = Analysis::where('user_id', $userId)
            ->where('type', $slug)
            ->first();

        if (!$analysis) {
            return '<p class="text-gray-500 text-center py-4">No hay análisis disponible.</p>';
        }

        if (Storage::exists($analysis->ai_response)) {
            $contenido = Storage::get($analysis->ai_response);
            
            // En lugar de borrar asteriscos y usar nl2br, convertimos el Markdown a HTML limpio
            return Str::markdown($contenido);
        }

        return '<p class="text-red-500 text-center py-4">Archivo de análisis no encontrado.</p>';
    }
}