<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use App\Models\Cuestionario;
use App\Models\Foto;

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

        Http::post('http://capilai-n8n:5678/webhook/enviar-datos', [
            'user_id' => $userId,
            'slug'    => $slug,
        ]);

        $fotos = Foto::where('user_id', $userId)->get();

        foreach ($fotos as $index => $foto) {
            Http::post('http://capilai-n8n:5678/webhook/enviar-fotos', [
                'slug_foto' => $foto->slug,
                'base64'    => $foto->base64,
            ]);
        }

        $cuestionario = Cuestionario::where('user_id', $userId)->firstOrFail();
        $contenidoArchivo = Storage::get($cuestionario->archivo_json);
        $datosCuestionario = json_decode($contenidoArchivo, true);

        Http::post('http://capilai-n8n:5678/webhook/enviar-cuestionario',$datosCuestionario);

        return response()->json([
            'status'  => 'ok',
            'message' => 'Datos, fotos y cuestionario enviados correctamente',
        ]);
    }
}