<?php

namespace App\Tags;

use Statamic\Tags\Tags;
use App\Models\Analysis;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class HairPhotos extends Tags
{
    public function index()
    {
        try {

            $userId = session('usuario_id');

            if (!$userId) {
                return '<p class="text-gray-500 text-center py-4">
                    Usuario no autenticado.
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

                $datofoto = $analysis->datofoto;

                if (!$datofoto) {

                    Log::warning('Analysis sin datofoto asociado', [
                        'analysis_id' => $analysis->id
                    ]);

                    $html .= '<p class="text-red-500 text-center py-4">
                        No se encontró datofoto asociado.
                    </p>';

                    continue;
                }

                if (!$datofoto->archivo_json) {

                    Log::warning('Datofoto sin archivo_json', [
                        'datofoto_id' => $datofoto->id
                    ]);

                    continue;
                }

                if (!Storage::exists($datofoto->archivo_json)) {

                    Log::warning('Archivo JSON no encontrado', [
                        'path' => $datofoto->archivo_json
                    ]);

                    $html .= '<p class="text-red-500 text-center py-4">
                        Archivo no encontrado: ' . e($datofoto->archivo_json) . '
                    </p>';

                    continue;
                }

                $contenido = Storage::get($datofoto->archivo_json);

                if ($contenido === false) {

                    Log::warning('No se pudo leer archivo JSON', [
                        'path' => $datofoto->archivo_json
                    ]);

                    continue;
                }

                $fecha = optional($analysis->created_at)
                    ? $analysis->created_at->format('d/m/Y')
                    : 'sin fecha';

                $html .= '
                    <div class="mb-8"
                         data-fecha="' . e($fecha) . '">
                        ' . Str::markdown($contenido) . '
                    </div>
                ';
            }

            return $html;

        } catch (\Exception $e) {

            Log::error('Error en HairPhotos tag', [
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