<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use App\Models\Cuestionario;
use App\Models\Foto;
use App\Models\Analysis;

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

        $fotos = Foto::where('user_id', $userId)->get();

        $cuestionario = Cuestionario::where('user_id', $userId)->firstOrFail();
        $contenidoArchivo = Storage::get($cuestionario->archivo_json);
        $datosCuestionario = json_decode($contenidoArchivo, true);

        $DatosImagenes = null;
        foreach ($fotos as $foto) {
            $DatosImagenes = Http::post('http://capilai-n8n:5678/webhook/enviar-fotos', [
                'slug_foto' => $foto->slug,
                'base64'    => $foto->base64,
            ]);
        }

        $features = $DatosImagenes->json();

        $analysisPrevio = Analysis::where('user_id', $userId)
            ->where('type', $slug)
            ->first();

        $Generar = true;
        $textoGenerado = null;

        if ($analysisPrevio) {

            $cuestionarioIgual = $analysisPrevio->cuestionario_json == $datosCuestionario;
            $fotosIguales = $analysisPrevio->fotos_json == $features;

            if ($cuestionarioIgual && $fotosIguales) {
                $Generar = false;
                $textoGenerado = $analysisPrevio->ai_response;
            }

        } else {

            $analisisCoincidentes = Analysis::where('cuestionario_json', $datosCuestionario)
                ->where('type', $slug)
                ->get();

            foreach ($analisisCoincidentes as $analisis) {

                if ($this->fotosSonIguales($analisis->fotos_json, $features)) {

                    $textoGenerado = $analisis->ai_response;

                    Analysis::create([
                        'user_id' => $userId,
                        'type' => $slug,
                        'cuestionario_json' => $datosCuestionario,
                        'fotos_json' => $features,
                        'ai_response' => $textoGenerado,
                    ]);

                    $Generar = false;
                    break;
                }
            }
        }

        if ($Generar) {

            Http::post('http://capilai-n8n:5678/webhook/enviar-datos', [
                'slug'    => $slug,
            ]);

            Http::post('http://capilai-n8n:5678/webhook/datos-imagenes', [
                'features_globales' => $features
            ]);

            $respuesta = Http::post('http://capilai-n8n:5678/webhook/enviar-cuestionario', $datosCuestionario);
            $textoGenerado = $respuesta->body();

            Analysis::updateOrCreate(
                ['user_id' => $userId, 'type' => $slug],
                [
                    'cuestionario_json' => $datosCuestionario,
                    'fotos_json' => $features,
                    'ai_response' => $textoGenerado,
                ]
            );
        }
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
        if (abs($densidadA - $densidadB) > $tolerancia) {
            return false;
        }

        return true;
    }
}