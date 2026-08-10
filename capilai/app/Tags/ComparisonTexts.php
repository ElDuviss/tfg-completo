<?php

namespace App\Tags;

use Statamic\Tags\Tags;
use App\Models\Comparison;
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

                if (empty($comparison->comparison_text)) {

                    Log::warning('Comparación vacía', [
                        'comparison_id' => $comparison->id
                    ]);

                    continue;
                }

                $html .= '
                    <div class="mb-8"
                        data-datofoto-nuevo="' . e($comparison->datofoto_nuevo_id) . '"
                        data-datofoto-antiguo="' . e($comparison->datofoto_antiguo_id) . '"
                    >
                        ' . Str::markdown($comparison->comparison_text) . '
                    </div>
                ';
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