<?php

namespace App\Tags;

use Statamic\Tags\Tags;
use App\Models\Analysis;
use Illuminate\Support\Facades\Storage;

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
            return 'No hay análisis disponible.';
        }

        if (Storage::exists($analysis->ai_response)) {
            $contenido = Storage::get($analysis->ai_response);
            $contenidoLimpio = str_replace('*', '', $contenido);
            return nl2br(e($contenidoLimpio));
        }

        return 'Archivo de análisis no encontrado.';
    }
}