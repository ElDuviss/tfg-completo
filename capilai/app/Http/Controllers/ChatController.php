<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use App\Models\Cuestionario;
use App\Models\Foto;
use App\Models\Datofoto;

class ChatController extends Controller
{
    public function enviar(Request $request)
    {
        $request->validate([
            'mensaje' => 'required|string'
        ]);

        $pregunta = $request->mensaje;
        $userId = session('usuario_id');
        $fotos = Foto::where('user_id', $userId)->get();

        $cuestionario = Cuestionario::where('user_id', $userId)->firstOrFail();
        $contenidoArchivo = Storage::get($cuestionario->archivo_json);
        $datosCuestionario = json_decode($contenidoArchivo, true);

        $datofoto = Datofoto::where('user_id', $userId)->latest()->first();
        $contenidoFotos = Storage::get($datofoto->archivo_json);
        $features = json_decode($contenidoFotos, true);

        Http::post('', $pregunta);

        $respuesta = Http::post('http://capilai-n8n:5678/webhook/enviar-cuestionario', $datosCuestionario);

        return response()->json([
            'user_id' => $userId,
            'respuesta' => "Mensaje recibido: " . $request->mensaje
        ]);
    }

}