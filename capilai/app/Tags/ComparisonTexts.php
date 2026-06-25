<?php

namespace App\Tags;

use Statamic\Tags\Tags;
use App\Models\Comparison;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ComparisonTexts extends Tags
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

            $comparisons = Comparison::where('user_id', $userId)
                ->orderBy('created_at', 'desc')
                ->limit(50)
                ->get();

            if ($comparisons->isEmpty()) {
                return '<p class="text-gray-500 text-center py-4">
                    No hay comparaciones disponibles.
                </p>';
            }

            $html = '';

            foreach ($comparisons as $comparison) {

                if (!isset($comparison->comparison_text)) {
                    continue;
                }

                if (Storage::exists($comparison->comparison_text)) {

                    $contenido = Storage::get($comparison->comparison_text);

                    if ($contenido === false) {

                        Log::warning('No se pudo leer comparación', [
                            'file' => $comparison->comparison_text
                        ]);

                        continue;
                    }

                    $html .= '
                        <div class="mb-8"
                            data-datofoto-nuevo="' . e($comparison->datofoto_nuevo_id) . '"
                            data-datofoto-antiguo="' . e($comparison->datofoto_antiguo_id) . '"
                        >
                            ' . Str::markdown($contenido) . '
                        </div>
                    ';

                } else {

                    Log::warning('Archivo de comparación no encontrado', [
                        'file' => $comparison->comparison_text
                    ]);

                    $html .= '
                        <p class="text-red-500 text-center py-4">
                            Archivo no encontrado: ' . e($comparison->comparison_text) . '
                        </p>
                    ';
                }
            }

            return $html;

        } catch (\Exception $e) {

            Log::error('Error en ComparisonTexts tag', [
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ]);

            return '<p class="text-red-500 text-center py-4">
                Error al cargar comparaciones.
            </p>';
        }
    }
}