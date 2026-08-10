<?php

namespace App\Tags;

use Statamic\Tags\Tags;
use App\Models\Analysis;
use Illuminate\Support\Facades\Log;

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
                ->latest()
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

                    continue;
                }

                $json = $datofoto->archivo_json;

                if (is_string($json)) {
                    $json = json_decode($json, true);
                }

                if (!is_array($json)) {

                    Log::error('archivo_json inválido', [
                        'datofoto_id' => $datofoto->id,
                        'valor' => $datofoto->archivo_json
                    ]);

                    continue;
                }

                $fecha = optional($analysis->created_at)
                    ? $analysis->created_at->format('d/m/Y')
                    : 'Sin fecha';

                $html .= '
                    <div class="mb-8 border rounded-lg p-4 bg-white shadow">

                        <h3 class="font-bold mb-3">
                            Análisis del ' . e($fecha) . '
                        </h3>

                        <pre class="bg-gray-100 p-4 rounded overflow-auto text-sm">'
                            . e(json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) .
                        '</pre>

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
                Error al cargar los datos.
            </p>';
        }
    }
}