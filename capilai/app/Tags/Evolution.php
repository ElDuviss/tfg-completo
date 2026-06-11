<?php

namespace App\Tags;

use Statamic\Tags\Tags;
use App\Models\Analysis;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class Evolution extends Tags
{
    public function historial()
    {
        Log::info("EVOLUTION TAG → Iniciado");

        // ⚠️ PRIMER PUNTO: ¿Existe el usuario?
        $userId = session('usuario_id');
        Log::info("EVOLUTION TAG → usuario_id en sesión:", ['userId' => $userId]);

        if (!$userId) {
            Log::warning("EVOLUTION TAG → No hay usuario en sesión");
            return [];
        }

        // ⚠️ SEGUNDO PUNTO: ¿Hay análisis?
        $analisis = Analysis::where('user_id', $userId)
            ->orderBy('created_at', 'asc')
            ->get();

        Log::info("EVOLUTION TAG → análisis encontrados:", ['count' => $analisis->count()]);

        if ($analisis->isEmpty()) {
            Log::warning("EVOLUTION TAG → No hay análisis para este usuario");
            return [];
        }

        $historial = [];

        foreach ($analisis as $item) {

            Log::info("EVOLUTION TAG → Procesando análisis:", ['id' => $item->id]);

            $jsonPath = $item->fotos_json;
            Log::info("EVOLUTION TAG → fotos_json:", ['path' => $jsonPath]);

            if (!$jsonPath || !Storage::exists($jsonPath)) {
                Log::error("EVOLUTION TAG → Archivo JSON no encontrado", ['path' => $jsonPath]);
                continue;
            }

            $jsonContent = Storage::get($jsonPath);
            Log::info("EVOLUTION TAG → contenido JSON:", ['content' => $jsonContent]);

            $json = json_decode($jsonContent, true);

            if (!$json) {
                Log::error("EVOLUTION TAG → Error al decodificar JSON", ['path' => $jsonPath]);
                continue;
            }

            $map = [
                'baja' => 1,
                'media' => 2,
                'alta' => 3,
                'no_visibles' => 1,
                'leves' => 2,
                'marcadas' => 3
            ];

            $entry = [
                'fecha' => $item->created_at->format('Y-m-d'),
                'miniaturizacion' => $map[$json['miniaturizacion']] ?? 0,
                'densidad_media' => floatval($json['densidad_media'] ?? 0),
                'coronilla' => $map[$json['coronilla']] ?? 0,
                'grasa' => $map[$json['grasa']] ?? 0,
                'entradas' => $map[$json['entradas']] ?? 0,
                'irritacion' => $map[$json['irritacion']] ?? 0,
            ];

            Log::info("EVOLUTION TAG → entrada añadida:", $entry);

            $historial[] = $entry;
        }

        Log::info("EVOLUTION TAG → historial final:", $historial);

        return $historial;
    }
}