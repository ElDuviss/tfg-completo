<?php

namespace App\Tags;

use Statamic\Tags\Tags;
use App\Models\Datofoto;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class Evolution extends Tags
{
    public function historial()
    {
        try {

            $userId = session('usuario_id');

            if (!$userId) {
                Log::warning("EVOLUTION TAG → No hay usuario en sesión");
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
                'baja' => 1,
                'media' => 2,
                'alta' => 3,
                'no_visibles' => 1,
                'leves' => 2,
                'marcadas' => 3
            ];

            foreach ($datofotos as $df) {

                if (!$df->archivo_json) {
                    Log::warning("EVOLUTION TAG → archivo_json vacío", [
                        'datofoto_id' => $df->id
                    ]);
                    continue;
                }

                if (!Storage::exists($df->archivo_json)) {
                    Log::warning("EVOLUTION TAG → Archivo JSON no encontrado", [
                        'path' => $df->archivo_json
                    ]);
                    continue;
                }

                $jsonContent = Storage::get($df->archivo_json);

                if ($jsonContent === false) {
                    Log::warning("EVOLUTION TAG → No se pudo leer archivo", [
                        'path' => $df->archivo_json
                    ]);
                    continue;
                }

                $json = json_decode($jsonContent, true);

                if (!is_array($json)) {
                    Log::error("EVOLUTION TAG → Error al decodificar JSON", [
                        'path' => $df->archivo_json
                    ]);
                    continue;
                }

                $historial[] = [
                    'fecha' => optional($df->created_at)->format('Y-m-d'),

                    'miniaturizacion' =>
                        $map[$json['miniaturizacion'] ?? null] ?? 0,

                    'densidad_media' =>
                        floatval($json['densidad_media'] ?? 0),

                    'coronilla' =>
                        $map[$json['coronilla'] ?? null] ?? 0,

                    'grasa' =>
                        $map[$json['grasa'] ?? null] ?? 0,

                    'entradas' =>
                        $map[$json['entradas'] ?? null] ?? 0,

                    'irritacion' =>
                        $map[$json['irritacion'] ?? null] ?? 0,
                ];
            }

            return $historial;

        } catch (\Exception $e) {

            Log::error("EVOLUTION TAG → Error general", [
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ]);

            return [];
        }
    }
}