<?php

namespace App\Tags;

use Statamic\Tags\Tags;
use App\Models\Comparison;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ComparisonTexts extends Tags
{
    public function index()
    {
        $userId = session('usuario_id');

        $comparisons = Comparison::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->get();

        if ($comparisons->isEmpty()) {
            return '<p class="text-gray-500 text-center py-4">No hay comparaciones disponibles.</p>';
        }

        $html = '';

        foreach ($comparisons as $comparison) {

            if (Storage::exists($comparison->comparison_text)) {

                $contenido = Storage::get($comparison->comparison_text);

                $html .= '
                    <div class="mb-8"
                        data-datofoto-nuevo="' . $comparison->datofoto_nuevo_id . '"
                        data-datofoto-antiguo="' . $comparison->datofoto_antiguo_id . '"
                    >
                        ' . Str::markdown($contenido) . '
                    </div>
                ';

            } else {

                $html .= '<p class="text-red-500 text-center py-4">
                    Archivo no encontrado: ' . $comparison->comparison_text . '
                </p>';
            }
        }

        return $html;
    }
}