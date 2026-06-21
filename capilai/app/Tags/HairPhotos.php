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

        if (!$userId) {
            return '<p class="text-gray-500 text-center py-4">Usuario no autenticado.</p>';
        }

        $analyses = Analysis::where('user_id', $userId)
            ->where('type', $slug)
            ->orderBy('created_at', 'desc')
            ->get();

        if ($analyses->isEmpty()) {
            return '<p class="text-gray-500 text-center py-4">No hay análisis disponible.</p>';
        }

        $html = '';

        foreach ($analyses as $analysis) {

            $datofoto = $analysis->datofoto;

            if (!$datofoto) {
                $html .= '<p class="text-red-500 text-center py-4">No se encontró datofoto asociado.</p>';
                continue;
            }

            $jsonPath = $datofoto->archivo_json;

            if (!$jsonPath || !Storage::exists($jsonPath)) {
                $html .= '<p class="text-red-500 text-center py-4">Archivo no encontrado: '.$jsonPath.'</p>';
                continue;
            }

            $contenido = Storage::get($jsonPath);

            $html .= '<div class="mb-8" data-fecha="'.$analysis->created_at->format('d/m/Y').'">'
                   . Str::markdown($contenido)
                   . '</div>';
        }

        return $html;
    }
}