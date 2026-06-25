<?php

namespace App\Tags;

use Statamic\Tags\Tags;
use App\Models\Analysis;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class HairAnalysis extends Tags
{
    public function index()
    {
        try {

            $userId = session('usuario_id');

            if (!$userId) {
                return '<p class="text-gray-500 text-center py-4">
                    No hay sesión activa.
                </p>';
            }

            $slug = $this->context->get('slug');

            if (!$slug) {
                return '<p class="text-gray-500 text-center py-4">
                    Tipo de análisis no especificado.
                </p>';
            }

            $analyses = Analysis::where('user_id', $userId)
                ->where('type', $slug)
                ->orderBy('created_at', 'desc')
                ->get();

            if ($analyses->isEmpty()) {
                return '<p class="text-gray-500 text-center py-4">
                    No hay análisis disponible.
                </p>';
            }

            $html = '';

            foreach ($analyses as $analysis) {

                if (!$analysis->ai_response) {
                    continue;
                }

                if (Storage::exists($analysis->ai_response)) {

                    $contenido = Storage::get($analysis->ai_response);

                    if ($contenido === false) {

                        Log::warning('No se pudo leer análisis AI', [
                            'file' => $analysis->ai_response
                        ]);

                        continue;
                    }

                    $fecha = optional($analysis->created_at)
                        ? $analysis->created_at->format('d/m/Y')
                        : 'sin fecha';

                    $html .= '
                        <div class="mb-8"
                            data-fecha="' . e($fecha) . '"
                        >
                            ' . Str::markdown($contenido) . '
                        </div>
                    ';

                } else {

                    Log::warning('Archivo de análisis no encontrado', [
                        'file' => $analysis->ai_response
                    ]);

                    $html .= '
                        <p class="text-red-500 text-center py-4">
                            Archivo no encontrado: ' . e($analysis->ai_response) . '
                        </p>
                    ';
                }
            }

            return $html;

        } catch (\Exception $e) {

            Log::error('Error en HairAnalysis tag', [
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ]);

            return '<p class="text-red-500 text-center py-4">
                Error al cargar los análisis.
            </p>';
        }
    }
}