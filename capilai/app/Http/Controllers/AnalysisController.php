<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Cuestionario;
use App\Models\Datofoto;
use App\Models\Analysis;
use App\Models\Usuario;
use App\Models\ChatMessage;

class AnalysisController extends Controller
{
    public function storeJson(Request $request)
    {
        $request->validate([
            'user_id' => 'required|integer|exists:usuarios,id',
            'slug'    => 'required|string|max:255',
        ]);

        try {

            $userId = $request->user_id;
            $slug   = $request->slug;

            $cuestionario = Cuestionario::where('user_id', $userId)
                ->orderBy('created_at', 'desc')
                ->firstOrFail();

            if (!Storage::exists($cuestionario->archivo_json)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontró el archivo del cuestionario.'
                ], 404);
            }

            $contenidoCuestionario = Storage::get($cuestionario->archivo_json);
            $datosCuestionario = json_decode($contenidoCuestionario, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return response()->json([
                    'success' => false,
                    'message' => 'El JSON del cuestionario es inválido.'
                ], 500);
            }

            $datofoto = Datofoto::where('user_id', $userId)
                ->orderBy('created_at', 'desc')
                ->firstOrFail();

            if (!Storage::exists($datofoto->archivo_json)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontró el archivo de características de imágenes.'
                ], 404);
            }

            $contenidoFotos = Storage::get($datofoto->archivo_json);
            $features = json_decode($contenidoFotos, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return response()->json([
                    'success' => false,
                    'message' => 'El JSON de características es inválido.'
                ], 500);
            }

            $analysisPrevio = Analysis::where('user_id', $userId)
                ->where('type', $slug)
                ->where('cuestionario_id', $cuestionario->id)
                ->where('datofoto_id', $datofoto->id)
                ->latest()
                ->first();

            $debeGenerarseAnalisis = true;
            $textoGenerado = null;

            if ($analysisPrevio) {

                if (!Storage::exists($analysisPrevio->ai_response)) {
                    Log::warning('Archivo de análisis no encontrado', [
                        'analysis_id' => $analysisPrevio->id
                    ]);
                } else {
                    $textoGenerado = Storage::get($analysisPrevio->ai_response);
                    $debeGenerarseAnalisis = false;
                }
            }

            if ($debeGenerarseAnalisis) {

                $analisisCoincidentes = Analysis::where('type', $slug)
                    ->where('cuestionario_id', $cuestionario->id)
                    ->where('datofoto_id', $datofoto->id)
                    ->get();

                foreach ($analisisCoincidentes as $analisis) {

                    if (!Storage::exists($analisis->ai_response)) {
                        continue;
                    }

                    $textoGenerado = Storage::get($analisis->ai_response);

                    Analysis::create([
                        'user_id'         => $userId,
                        'type'            => $slug,
                        'cuestionario_id' => $cuestionario->id,
                        'datofoto_id'     => $datofoto->id,
                        'ai_response'     => $analisis->ai_response,
                    ]);

                    $debeGenerarseAnalisis = false;
                    break;
                }
            }

            if ($debeGenerarseAnalisis) {

                Http::timeout(60)->post(
                    'http://capilai-n8n:5678/webhook/enviar-datos',
                    [
                        'slug' => $slug,
                    ]
                );

                Http::timeout(60)->post(
                    'http://capilai-n8n:5678/webhook/datos-imagenes',
                    [
                        'features_globales' => $features
                    ]
                );

                $respuesta = Http::timeout(60)->post(
                    'http://capilai-n8n:5678/webhook/enviar-cuestionario',
                    $datosCuestionario
                );

                if (!$respuesta->successful()) {

                    Log::error('Error en webhook enviar-cuestionario', [
                        'status' => $respuesta->status(),
                        'body'   => $respuesta->body(),
                    ]);

                    return response()->json([
                        'success' => false,
                        'message' => 'Error al generar el análisis mediante IA.'
                    ], 500);
                }

                $textoGenerado = $respuesta->body();

                $archivoTexto = "analysis/Respuestas/texto_user_{$userId}_{$slug}_" . time() . ".txt";

                Storage::put($archivoTexto, $textoGenerado);

                Analysis::create([
                    'user_id'         => $userId,
                    'type'            => $slug,
                    'cuestionario_id' => $cuestionario->id,
                    'datofoto_id'     => $datofoto->id,
                    'ai_response'     => $archivoTexto,
                ]);
            }

            return response()->json([
                'success' => true,
                'texto'   => $textoGenerado
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {

            Log::warning('Datos no encontrados para generar análisis', [
                'user_id' => $request->user_id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'No existen datos suficientes para generar el análisis.'
            ], 404);

        } catch (\Exception $e) {

            Log::error('Error en AnalysisController@storeJson', [
                'message' => $e->getMessage(),
                'line'    => $e->getLine(),
                'file'    => $e->getFile(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ha ocurrido un error interno.'
            ], 500);
        }
    }

    public function destroyAccount(Request $request)
    {
        $userId = session('usuario_id');

        if (!$userId) {
            return redirect('/')
                ->with('error', 'No hay ninguna sesión activa.');
        }

        $usuario = Usuario::find($userId);

        if (!$usuario) {
            return redirect('/')
                ->with('error', 'El usuario no existe.');
        }

        $usuario->delete();

        auth()->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/')
            ->with('success', 'Cuenta eliminada correctamente.');
    }
}