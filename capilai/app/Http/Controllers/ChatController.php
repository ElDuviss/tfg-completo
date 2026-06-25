<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use App\Models\Cuestionario;
use App\Models\Datofoto;
use App\Models\ChatMessage;

class ChatController extends Controller
{
    public function enviar(Request $request)
    {
        $request->validate([
            'mensaje' => 'required|string|max:5000'
        ]);

        try {

            $pregunta = $request->mensaje;
            $userId = session('usuario_id');

            if (!$userId) {
                return response()->json([
                    'error' => 'Sesión no válida.'
                ], 401);
            }

            $cuestionario = Cuestionario::where('user_id', $userId)
                ->orderBy('created_at', 'desc')
                ->firstOrFail();

            if (!Storage::exists($cuestionario->archivo_json)) {
                return response()->json([
                    'error' => 'No existe el archivo del cuestionario.'
                ], 404);
            }

            $contenidoArchivo = Storage::get($cuestionario->archivo_json);
            $datosCuestionario = json_decode($contenidoArchivo, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return response()->json([
                    'error' => 'El cuestionario contiene un JSON inválido.'
                ], 500);
            }

            $datofoto = Datofoto::where('user_id', $userId)
                ->orderBy('created_at', 'desc')
                ->firstOrFail();

            if (!Storage::exists($datofoto->archivo_json)) {
                return response()->json([
                    'error' => 'No existe el archivo de datos fotográficos.'
                ], 404);
            }

            $contenidoFotos = Storage::get($datofoto->archivo_json);
            $features = json_decode($contenidoFotos, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return response()->json([
                    'error' => 'Los datos fotográficos contienen un JSON inválido.'
                ], 500);
            }

            $respuesta = Http::timeout(60)->post(
                'http://capilai-n8n:5678/webhook/preguntar',
                [
                    'pregunta'     => $pregunta,
                    'cuestionario' => $datosCuestionario,
                    'datos_fotos'  => $features
                ]
            );

            if (!$respuesta->successful()) {

                Log::error('Error en webhook preguntar', [
                    'status' => $respuesta->status(),
                    'body' => $respuesta->body()
                ]);

                return response()->json([
                    'error' => 'Error al comunicarse con el sistema de IA.'
                ], 500);
            }

            $textoRespuesta = $respuesta->body();

            $timestamp = time();

            $archivoPregunta =
                "analysis/Chat/Pregunta/user_{$userId}_chat_{$timestamp}.txt";

            Storage::put($archivoPregunta, $pregunta);

            $archivoRespuesta =
                "analysis/Chat/Respuesta/user_{$userId}_chat_{$timestamp}.txt";

            Storage::put($archivoRespuesta, $textoRespuesta);

            ChatMessage::updateOrCreate(
                ['user_id' => $userId],
                [
                    'question' => $archivoPregunta,
                    'answer'   => $archivoRespuesta
                ]
            );

            $chat = ChatMessage::where('user_id', $userId)->first();

            if (!$chat) {

                Log::warning('No se pudo recuperar el chat recién creado', [
                    'user_id' => $userId
                ]);

                return response()->json([
                    'error' => 'Error al guardar el historial del chat.'
                ], 500);
            }

            $archivoPreguntaActual = $chat->question;
            $archivoRespuestaActual = $chat->answer;

            $prefijoPregunta =
                "analysis/Chat/Pregunta/user_{$userId}_";

            $prefijoRespuesta =
                "analysis/Chat/Respuesta/user_{$userId}_";

            $preguntas = Storage::files('analysis/Chat/Pregunta');

            foreach ($preguntas as $archivo) {

                if (
                    str_starts_with($archivo, $prefijoPregunta) &&
                    $archivo !== $archivoPreguntaActual
                ) {
                    Storage::delete($archivo);
                }
            }

            $respuestas = Storage::files('analysis/Chat/Respuesta');

            foreach ($respuestas as $archivo) {

                if (
                    str_starts_with($archivo, $prefijoRespuesta) &&
                    $archivo !== $archivoRespuestaActual
                ) {
                    Storage::delete($archivo);
                }
            }

            $respuestaLimpia = str_replace('*', '', $textoRespuesta);
            $respuestaFormateada = nl2br(e($respuestaLimpia));

            return response()->json([
                'respuesta' => $respuestaFormateada
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {

            Log::warning('Faltan datos para el chat', [
                'user_id' => session('usuario_id')
            ]);

            return response()->json([
                'error' => 'No existen datos suficientes para realizar la consulta.'
            ], 404);

        } catch (\Exception $e) {

            Log::error('Error en ChatController@enviar', [
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ]);

            return response()->json([
                'error' => 'Ha ocurrido un error interno.'
            ], 500);
        }
    }
}