<?php

namespace App\Http\Controllers;

use App\Models\Datofoto;
use Illuminate\Support\Facades\Storage;
use App\Models\Foto;
use Illuminate\Support\Facades\Http;

class DatofotoController extends Controller
{
    public function guardar()
    {
        $userId = session('usuario_id');

        if (!$userId) {
            return response()->json(['error' => 'Usuario no autenticado'], 401);
        }

        $fotos = Foto::where('user_id', $userId)->get();

        $DatosImagenes = null;
        foreach ($fotos as $foto) {
            $DatosImagenes = Http::post('http://capilai-n8n:5678/webhook/enviar-fotos', [
                'slug_foto' => $foto->slug,
                'base64'    => $foto->base64,
            ]);
        }

        $features = $DatosImagenes->json();
        $contenidoJson = json_encode($features, JSON_PRETTY_PRINT);
        $nombreArchivo = "datofotos/user_{$userId}_" . time() . ".json";
        Storage::put($nombreArchivo, $contenidoJson);

        Datofoto::create([
            'user_id' => $userId,
            'archivo_json' => $nombreArchivo
        ]);

        return response()->json([
            'success' => true,
            'archivo' => $nombreArchivo
        ]);
    }
}