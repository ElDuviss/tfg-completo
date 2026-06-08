<?php

namespace App\Tags;

use Statamic\Tags\Tags;
use App\Models\Analysis;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class HairPhotos extends Tags
{
    public function index()
    {
        $slug = $this->context->get('slug');
        $userId = session('usuario_id');

        $analyses = Analysis::where('user_id', $userId)
            ->where('type', $slug)
            ->orderBy('created_at', 'desc')
            ->get();

        if ($analyses->isEmpty()) {
            return '<p class="text-gray-500 text-center py-4">No hay análisis disponible.</p>';
        }

        $html = '';

        foreach ($analyses as $analysis) {
            if (Storage::exists($analysis->fotos_json)) {
                $contenido = Storage::get($analysis->fotos_json);
                $html .= '<div class="mb-8" data-fecha="'.$analysis->created_at->format('d/m/Y').'">'.Str::markdown($contenido).'</div>';
            } else {
                $html .= '<p class="text-red-500 text-center py-4">Archivo no encontrado: '.$analysis->fotos_json.'</p>';
            }
        }

        return $html;
    }
}