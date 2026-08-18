<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
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
                ->latest()
                ->first();

            if (!$cuestionario) {

                return response()->json([
                    'error' => 'No existe cuestionario.'
                ], 404);

            }

            $datosCuestionario = $cuestionario->archivo_json;

            if (is_string($datosCuestionario)) {

                $datosCuestionario = json_decode($datosCuestionario, true);

            }

            if (!is_array($datosCuestionario)) {

                Log::error('Cuestionario inválido en ChatController', [
                    'valor' => $datosCuestionario
                ]);

                return response()->json([
                    'error' => 'El cuestionario contiene un JSON inválido.'
                ], 500);

            }

            $datofoto = Datofoto::where('user_id', $userId)
                ->latest()
                ->first();

            if (!$datofoto) {

                return response()->json([
                    'error' => 'No existen datos fotográficos.'
                ], 404);

            }

            $features = $datofoto->archivo_json;

            if (is_string($features)) {

                $features = json_decode($features, true);

            }

            if (!is_array($features)) {

                Log::error('Features inválidos en ChatController', [
                    'valor' => $features
                ]);

                return response()->json([
                    'error' => 'Los datos fotográficos contienen un JSON inválido.'
                ], 500);

            }

            try {
    Log::info('========== INICIO PETICIÓN N8N ==========');

    Log::info('URL n8n', [
        'url' => 'https://n8n-xigf.onrender.com/webhook/preguntar'
    ]);

    Log::info('Pregunta enviada', [
        'pregunta' => $pregunta
    ]);

    Log::info('Datos cuestionario', [
        'cuestionario' => $datosCuestionario
    ]);

    Log::info('Datos fotos', [
        'datos_fotos' => $features
    ]);

    $respuesta = Http::timeout(120)
        ->withHeaders([
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ])
        ->post(
            'https://n8n-xigf.onrender.com/webhook/preguntar',
            [
                'pregunta' => $pregunta,
                'cuestionario' => $datosCuestionario,
                'datos_fotos' => $features
            ]
        );

    Log::info('========== RESPUESTA DE N8N ==========');

    Log::info('Status HTTP n8n', [
        'status' => $respuesta->status()
    ]);

    Log::info('Respuesta completa n8n', [
        'body' => $respuesta->body()
    ]);

    Log::info('Headers n8n', [
        'headers' => $respuesta->headers()
    ]);

    if (!$respuesta->successful()) {
        Log::error('N8N HA DEVUELTO UN ERROR', [
            'status' => $respuesta->status(),
            'body' => $respuesta->body()
        ]);
    }

    Log::info('========== FIN PETICIÓN N8N ==========');

    } catch (\Throwable $e) {

        Log::error('========== ERROR COMUNICANDO CON N8N ==========');

        Log::error('Mensaje', [
            'message' => $e->getMessage()
        ]);

        Log::error('Archivo', [
            'file' => $e->getFile()
        ]);

        Log::error('Línea', [
            'line' => $e->getLine()
        ]);

        Log::error('Trace', [
            'trace' => $e->getTraceAsString()
        ]);

        throw $e;
    }

            if (!$respuesta->successful()) {

                Log::error('Error webhook preguntar n8n', [

                    'status' => $respuesta->status(),

                    'body' => $respuesta->body()

                ]);

                return response()->json([

                    'error' => 'Error al comunicarse con la IA.'

                ],500);

            }

            $textoRespuesta = $respuesta->body();

            if(empty($textoRespuesta)){


                return response()->json([

                    'error'=>'La IA devolvió una respuesta vacía.'

                ],500);

            }

            ChatMessage::create([

                'user_id' => $userId,

                'question' => $pregunta,

                'answer' => $textoRespuesta

            ]);

            $respuestaLimpia = str_replace(
                '*',
                '',
                $textoRespuesta
            );

            $respuestaFormateada = nl2br(
                e($respuestaLimpia)
            );

            return response()->json([

                'respuesta' => $respuestaFormateada

            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {

            Log::warning('Datos no encontrados en ChatController', [

                'user_id'=>session('usuario_id')

            ]);

            return response()->json([

                'error'=>'No existen datos suficientes.'

            ],404);

        } catch (\Exception $e) {

            Log::error('Error en ChatController@enviar',[

                'message'=>$e->getMessage(),

                'line'=>$e->getLine(),

                'file'=>$e->getFile()

            ]);

            return response()->json([

                'error'=>'Ha ocurrido un error interno.'

            ],500);

        }

    }
}