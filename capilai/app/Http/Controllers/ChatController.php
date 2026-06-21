<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use App\Models\Cuestionario;
use App\Models\Datofoto;
use App\Models\ChatMessage;
use Illuminate\Support\Facades\Log;

class ChatController extends Controller
{
    public function enviar(Request $request)
    {
        $request->validate([
            'mensaje' => 'required|string'
        ]);

        $pregunta = $request->mensaje;
        $userId = session('usuario_id');

        $cuestionario = Cuestionario::where('user_id', $userId)
        ->orderBy('created_at', 'desc')
        ->first();
        $contenidoArchivo = Storage::get($cuestionario->archivo_json);
        $datosCuestionario = json_decode($contenidoArchivo, true);

        $datofoto = Datofoto::where('user_id', $userId)
        ->orderBy('created_at', 'desc')
        ->first();
        $contenidoFotos = Storage::get($datofoto->archivo_json);
        $features = json_decode($contenidoFotos, true);

        $respuesta = Http::post('http://capilai-n8n:5678/webhook/preguntar', [
            'pregunta'      => $pregunta,
            'cuestionario'  => $datosCuestionario,
            'datos_fotos'   => $features
        ]);

        $textoRespuesta = $respuesta->body();

        $archivoPregunta = "analysis/Chat/Pregunta/user_{$userId}_chat_" . time() . ".txt";
        Storage::put($archivoPregunta, $pregunta);

        $archivoRespuesta = "analysis/Chat/Respuesta/user_{$userId}_chat_" . time() . ".txt";
        Storage::put($archivoRespuesta, $textoRespuesta);

        ChatMessage::updateOrCreate(
            ['user_id' => $userId],
            [
                'question' => $archivoPregunta,
                'answer'   => $archivoRespuesta
            ]
        );

        $chat = ChatMessage::where('user_id', $userId)->first();

        $archivoPreguntaActual = $chat->question;
        $archivoRespuestaActual = $chat->answer;

        $prefijoPregunta = "analysis/Chat/Pregunta/user_{$userId}_";
        $prefijoRespuesta = "analysis/Chat/Respuesta/user_{$userId}_";

        $preguntas = Storage::files('analysis/Chat/Pregunta');

        foreach ($preguntas as $archivo) {
            if (str_starts_with($archivo, $prefijoPregunta) && $archivo !== $archivoPreguntaActual) {
                Storage::delete($archivo);
            }
        }

        $respuestas = Storage::files('analysis/Chat/Respuesta');

        foreach ($respuestas as $archivo) {
            if (str_starts_with($archivo, $prefijoRespuesta) && $archivo !== $archivoRespuestaActual) {
                Storage::delete($archivo);
            }
        }

        $respuestaLimpia = str_replace('*', '', $textoRespuesta);
        $respuestaFormateada = nl2br(e($respuestaLimpia));

        return response()->json([
            'respuesta' => $respuestaFormateada
        ]);
    }
}