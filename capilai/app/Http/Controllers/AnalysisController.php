<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\Cuestionario;
use App\Models\Datofoto;
use App\Models\Analysis;
use Illuminate\Support\Facades\Http;

class AnalysisController extends Controller
{
    public function storeJson(Request $request)
    {
        $request->validate([
            'user_id' => 'required|integer',
            'slug'    => 'required|string',
        ]);

        $userId = $request->user_id;
        $slug   = $request->slug;
        $cuestionario = Cuestionario::where('user_id', $userId)->firstOrFail();
        $contenidoCuestionario = Storage::get($cuestionario->archivo_json);
        $datosCuestionario = json_decode($contenidoCuestionario, true);
        $datofoto = Datofoto::where('user_id', $userId)->latest()->first();

        if (!$datofoto) {
            return response()->json(['error' => 'No existen datos de fotos'], 404);
        }

        $contenidoFotos = Storage::get($datofoto->archivo_json);
        $features = json_decode($contenidoFotos, true);

        $analysisPrevio = Analysis::where('user_id', $userId)
            ->where('type', $slug)
            ->first();

        $Generar = true;
        $textoGenerado = null;

        if ($analysisPrevio) {
            $cuestionarioIgual = $analysisPrevio->cuestionario_json == json_encode($datosCuestionario);
            $fotosIguales = $analysisPrevio->fotos_json == json_encode($features);

            if ($cuestionarioIgual && $fotosIguales) {
                $Generar = false;
                $textoGenerado = $analysisPrevio->ai_response;
            }

        } else {

            $analisisCoincidentes = Analysis::where('type', $slug)->get();

            foreach ($analisisCoincidentes as $analisis) {

                if ($this->fotosSonIguales(json_decode($analisis->fotos_json, true), $features)) {

                    $textoGenerado = $analisis->ai_response;

                    Analysis::create([
                        'user_id' => $userId,
                        'type' => $slug,
                        'cuestionario_json' => json_encode($datosCuestionario, JSON_PRETTY_PRINT),
                        'fotos_json' => json_encode($features, JSON_PRETTY_PRINT),
                        'ai_response' => $textoGenerado,
                    ]);

                    $Generar = false;
                    break;
                }
            }
        }

        if ($Generar) {

            Http::post('http://capilai-n8n:5678/webhook/enviar-datos', [
                'slug' => $slug,
            ]);

            Http::post('http://capilai-n8n:5678/webhook/datos-imagenes', [
                'features_globales' => $features
            ]);

            $respuesta = Http::post('http://capilai-n8n:5678/webhook/enviar-cuestionario', $datosCuestionario);
            $textoGenerado = $respuesta->body();

            $archivoCuestionario = "analysis/Cuestionarios/cuestionario_user_{$userId}.json";
            Storage::put($archivoCuestionario, json_encode($datosCuestionario, JSON_PRETTY_PRINT));

            $archivoFotos = "analysis/AnalisisFoto/fotos_user_{$userId}.json";
            Storage::put($archivoFotos, json_encode($features, JSON_PRETTY_PRINT));

            $archivoTexto = "analysis/Respuestas/texto_user_{$userId}_{$slug}_" . time() . ".txt";
            Storage::put($archivoTexto, $textoGenerado);

            Analysis::updateOrCreate(
                ['user_id' => $userId, 'type' => $slug],
                [
                    'cuestionario_json' => $archivoCuestionario,
                    'fotos_json'        => $archivoFotos,
                    'ai_response'       => $archivoTexto,
                ]
            );
        }

        return response()->json([
            'success' => true,
            'texto' => $textoGenerado
        ]);
    }

    private function fotosSonIguales($a, $b)
    {
        if (!$a || !$b) return false;

        $keysExactas = [
            'color_cabello',
            'miniaturizacion',
            'coronilla',
            'grasa',
            'entradas',
            'irritacion'
        ];

        foreach ($keysExactas as $key) {
            if (($a[$key] ?? null) !== ($b[$key] ?? null)) {
                return false;
            }
        }

        $densidadA = $a['densidad_media'] ?? null;
        $densidadB = $b['densidad_media'] ?? null;

        $tolerancia = 0.05;
        return abs($densidadA - $densidadB) <= $tolerancia;
    }
}