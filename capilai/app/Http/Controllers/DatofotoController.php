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
        $fotoFrontal = null;
        $fotoSuperior = null;
        $fotoIzquierda = null;
        $fotoDerecha = null;

        $DatosImagenes = null;

        foreach ($fotos as $foto) {

            switch ($foto->slug) {
                case 'foto-frontal':
                    $fotoFrontal = $foto->id;
                    break;
                case 'foto-superior':
                    $fotoSuperior = $foto->id;
                    break;
                case 'foto-lateral-izquierda':
                    $fotoIzquierda = $foto->id;
                    break;
                case 'foto-lateral-derecha':
                    $fotoDerecha = $foto->id;
                    break;
            }

            if (!Storage::disk('local')->exists($foto->base64)) {
                continue;
            }

            $contenido = Storage::disk('local')->get($foto->base64);
            $base64 = base64_encode($contenido);

            $DatosImagenes = Http::post('http://capilai-n8n:5678/webhook/enviar-fotos', [
                'slug_foto' => $foto->slug,
                'base64'    => $base64,
            ]);
        }

        $features = $DatosImagenes->json();
        $contenidoJson = json_encode($features, JSON_PRETTY_PRINT);

        $nombreArchivo = "datofotos/user_{$userId}_" . time() . ".json";
        Storage::disk('local')->put($nombreArchivo, $contenidoJson);

        $registro = Datofoto::create([
            'user_id' => $userId,
            'archivo_json' => $nombreArchivo,
            'foto_frontal_id' => $fotoFrontal,
            'foto_superior_id' => $fotoSuperior,
            'foto_lateral_izquierda_id' => $fotoIzquierda,
            'foto_lateral_derecha_id' => $fotoDerecha,
        ]);

        return response()->json([
            'success' => true,
            'archivo' => $nombreArchivo,
            'registro_id' => $registro->id
        ]);
    }
}