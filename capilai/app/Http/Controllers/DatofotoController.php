<?php

namespace App\Http\Controllers;

use App\Models\Datofoto;
use App\Models\Foto;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DatofotoController extends Controller
{
    public function guardar()
    {
        try {

            $userId = session('usuario_id');

            if (!$userId) {
                return response()->json([
                    'error' => 'Usuario no autenticado'
                ], 401);
            }

            $fotos = Foto::where('user_id', $userId)->get();

            if ($fotos->isEmpty()) {
                return response()->json([
                    'error' => 'No existen fotografías para procesar.'
                ], 404);
            }

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

                if (empty($foto->base64)) {

                    Log::warning('Foto sin base64 en BD', [
                        'foto_id' => $foto->id
                    ]);

                    continue;
                }

                $respuesta = Http::timeout(60)->post(
                    'https://0.0.0.0:10000/webhook/enviar-fotos',
                    [
                        'slug_foto' => $foto->slug,
                        'base64' => $foto->base64,
                    ]
                );

                if (!$respuesta->successful()) {

                    Log::error('Error en webhook enviar-fotos', [
                        'status' => $respuesta->status(),
                        'body' => $respuesta->body(),
                        'foto_id' => $foto->id
                    ]);

                    continue;
                }

                $DatosImagenes = $respuesta;
            }

            if (!$DatosImagenes) {

                return response()->json([
                    'error' => 'No se pudo procesar ninguna fotografía.'
                ], 500);
            }

            $features = $DatosImagenes->json();

            if (!is_array($features)) {

                Log::error('Respuesta JSON inválida desde n8n', [
                    'user_id' => $userId,
                    'respuesta' => $DatosImagenes->body()
                ]);

                return response()->json([
                    'error' => 'No se recibieron características válidas.'
                ], 500);
            }

            $jsonFeatures = json_encode($features, JSON_UNESCAPED_UNICODE);

            if ($jsonFeatures === false) {

                Log::error('Error convirtiendo características a JSON', [
                    'user_id' => $userId,
                    'error' => json_last_error_msg()
                ]);

                return response()->json([
                    'error' => 'No se pudo convertir el análisis.'
                ], 500);
            }


            $registro = Datofoto::create([

                'user_id' => $userId,

                'archivo_json' => $jsonFeatures,

                'foto_frontal_id' => $fotoFrontal,

                'foto_superior_id' => $fotoSuperior,

                'foto_lateral_izquierda_id' => $fotoIzquierda,

                'foto_lateral_derecha_id' => $fotoDerecha,

            ]);


            if (!$registro) {

                Log::error('Error creando registro Datofoto', [
                    'user_id' => $userId
                ]);

                return response()->json([
                    'error' => 'No se pudo registrar el análisis.'
                ], 500);
            }


            return response()->json([

                'success' => true,

                'registro_id' => $registro->id,

                'features' => $features

            ]);


        } catch (\Exception $e) {


            Log::error('Error en DatofotoController@guardar', [

                'message' => $e->getMessage(),

                'line' => $e->getLine(),

                'file' => $e->getFile(),

                'user_id' => session('usuario_id')

            ]);


            return response()->json([

                'error' => 'Ha ocurrido un error interno.'

            ], 500);

        }
    }
}