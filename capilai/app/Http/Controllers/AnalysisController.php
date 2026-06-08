<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\Cuestionario;
use App\Models\Datofoto;
use App\Models\Analysis;
use App\Models\Usuario;
use App\Models\ChatMessage;
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
        $contenidoFotos = Storage::get($datofoto->archivo_json);
        $features = json_decode($contenidoFotos, true);

        $analysisPrevio = Analysis::where('user_id', $userId)
            ->where('type', $slug)
            ->latest()
            ->first();

        $Generar = true;
        $textoGenerado = null;

        if ($analysisPrevio) {

            $prevCuestionario = json_decode(Storage::get($analysisPrevio->cuestionario_json), true);
            $prevFotos = json_decode(Storage::get($analysisPrevio->fotos_json), true);

            $cuestionarioIgual = $prevCuestionario == $datosCuestionario;
            $fotosIguales = $prevFotos == $features;

            if ($cuestionarioIgual && $fotosIguales) {

                $textoGenerado = Storage::get($analysisPrevio->ai_response);
                $Generar = false;
            }
        }

        if ($Generar && !$analysisPrevio) {

            $analisisCoincidentes = Analysis::where('type', $slug)->get();

            foreach ($analisisCoincidentes as $analisis) {

                $prevCuestionario = json_decode(Storage::get($analisis->cuestionario_json), true);
                $prevFotos = json_decode(Storage::get($analisis->fotos_json), true);

                $cuestionarioCoincide = $prevCuestionario == $datosCuestionario;
                $fotosCoinciden = $prevFotos == $features;

                if ($cuestionarioCoincide && $fotosCoinciden) {

                    $textoGenerado = Storage::get($analisis->ai_response);

                    Analysis::create([
                        'user_id' => $userId,
                        'type' => $slug,
                        'cuestionario_json' => $analisis->cuestionario_json,
                        'fotos_json' => $analisis->fotos_json,
                        'ai_response' => $analisis->ai_response,
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

            $archivoCuestionario = "analysis/Cuestionarios/cuestionario_user_{$userId}_" . time() . ".json";
            Storage::put($archivoCuestionario, json_encode($datosCuestionario, JSON_PRETTY_PRINT));

            $archivoFotos = "analysis/AnalisisFoto/fotos_user_{$userId}_" . time() . ".json";
            Storage::put($archivoFotos, json_encode($features, JSON_PRETTY_PRINT));

            $archivoTexto = "analysis/Respuestas/texto_user_{$userId}_{$slug}_" . time() . ".txt";
            Storage::put($archivoTexto, $textoGenerado);

            Analysis::create([
                'user_id' => $userId,
                'type' => $slug,
                'cuestionario_json' => $archivoCuestionario,
                'fotos_json' => $archivoFotos,
                'ai_response' => $archivoTexto,
            ]);
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

    public function destroyAccount(Request $request)
    {
        $user = session('usuario_id');
        if (!$user) {
            return redirect('/')->with('error', 'No hay ninguna sesión activa.');
        }

        $carpetas = [
            'analysis/Chat/Pregunta',
            'analysis/Chat/Respuesta',
            'fotos',
            'cuestionarios',
            'datofotos'
        ];

        foreach ($carpetas as $carpeta) {

            if (Storage::disk('local')->exists($carpeta)) {

                $archivos = Storage::disk('local')->files($carpeta);

                foreach ($archivos as $archivo) {

                    if (str_starts_with($archivo, "{$carpeta}/user_{$user}_")) {
                        Storage::disk('local')->delete($archivo);
                    }
                }
            }
        }

        Analysis::where('user_id', $user)->update(['user_id' => null]);
        Datofoto::where('user_id', $user)->delete();
        Cuestionario::where('user_id', $user)->delete();
        ChatMessage::where('user_id', $user)->delete();
        Usuario::where('id', $user)->delete();

        auth()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/')->with('success', 'Cuenta eliminada correctamente.');
    }

}