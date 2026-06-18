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
        $userId = session('usuario_id');

        if (!$userId) {
            Log::warning("EVOLUTION TAG → No hay usuario en sesión");
            return [];
        }

        $analisis = Analysis::where('user_id', $userId)
            ->orderBy('created_at', 'asc')
            ->get();

        if ($analisis->isEmpty()) {
            return [];
        }

        $historial = [];

        foreach ($analisis as $item) {

            $jsonPath = $item->fotos_json;

            if (!$jsonPath || !Storage::exists($jsonPath)) {
                continue;
            }

            $jsonContent = Storage::get($jsonPath);

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

            $historial[] = $entry;
        }

        return $historial;
    }
}