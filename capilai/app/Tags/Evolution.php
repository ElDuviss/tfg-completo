<?php

namespace App\Tags;

use Statamic\Tags\Tags;
use App\Models\Datofoto;
use Illuminate\Support\Facades\Log;

class Evolution extends Tags
{
    public function historial()
    {
        try {

            $userId = session('usuario_id');

            if (!$userId) {
                Log::warning('EVOLUTION TAG -> No hay usuario en sesión');
                return [];
            }

            $datofotos = Datofoto::where('user_id', $userId)
                ->orderBy('created_at', 'asc')
                ->get();

            if ($datofotos->isEmpty()) {
                return [];
            }

            $historial = [];

            $map = [
                'baja'         => 1,
                'media'        => 2,
                'alta'         => 3,
                'no_visibles'  => 1,
                'leves'        => 2,
                'marcadas'     => 3,
            ];

            foreach ($datofotos as $df) {

                $json = $df->archivo_json;

                // Si viene como string, convertirlo
                if (is_string($json)) {
                    $json = json_decode($json, true);
                }

                if (!is_array($json)) {

                    Log::error('EVOLUTION TAG -> archivo_json inválido', [
                        'datofoto_id' => $df->id,
                        'value' => $df->archivo_json,
                    ]);

                    continue;
                }

                $historial[] = [
                    'fecha' => $df->created_at?->format('Y-m-d'),

                    'miniaturizacion' => $map[$json['miniaturizacion'] ?? ''] ?? 0,

                    'densidad_media' => isset($json['densidad_media'])
                        ? (float) $json['densidad_media']
                        : 0,

                    'coronilla' => $map[$json['coronilla'] ?? ''] ?? 0,

                    'grasa' => $map[$json['grasa'] ?? ''] ?? 0,

                    'entradas' => $map[$json['entradas'] ?? ''] ?? 0,

                    'irritacion' => $map[$json['irritacion'] ?? ''] ?? 0,
                ];
            }

            return $historial;

        } catch (\Exception $e) {

            Log::error('EVOLUTION TAG -> Error general', [
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]);

            return [];
        }
    }
}