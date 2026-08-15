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

                /*
                 * IMPORTANTE:
                 * Este elemento debe contener ÚNICAMENTE el JSON,
                 * porque el JavaScript hace JSON.parse(innerText).
                 */
                $html .= '
                    <pre
                        data-fecha="' . e($fecha) . '"
                        class="hidden"
                    >' . e(json_encode(
                        $json,
                        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                    )) . '</pre>
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