<?php

namespace App\Http\Controllers;

use App\Models\Datofoto;
use App\Models\Foto;
use Illuminate\Support\Facades\Storage;
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

                if (!Storage::disk('local')->exists($foto->base64)) {

                    Log::warning(
                        'Archivo de fotografía inexistente',
                        [
                            'foto_id' => $foto->id,
                            'archivo' => $foto->base64
                        ]
                    );

                    continue;
                }

                $contenido = Storage::disk('local')->get(
                    $foto->base64
                );

                $base64 = base64_encode($contenido);

                $DatosImagenes = Http::timeout(60)->post(
                    'http://capilai-n8n:5678/webhook/enviar-fotos',
                    [
                        'slug_foto' => $foto->slug,
                        'base64'    => $base64,
                    ]
                );

                if (!$DatosImagenes->successful()) {

                    Log::error(
                        'Error en webhook enviar-fotos',
                        [
                            'status' => $DatosImagenes->status(),
                            'body' => $DatosImagenes->body(),
                            'foto_id' => $foto->id
                        ]
                    );

                    continue;
                }
            }

            if (!$DatosImagenes) {

                return response()->json([
                    'error' => 'No se pudo procesar ninguna fotografía.'
                ], 500);
            }

            $features = $DatosImagenes->json();

            if (!$features) {

                Log::error(
                    'Respuesta JSON vacía desde n8n',
                    [
                        'user_id' => $userId
                    ]
                );

                return response()->json([
                    'error' => 'No se recibieron características válidas.'
                ], 500);
            }

            $contenidoJson = json_encode(
                $features,
                JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
            );

            if ($contenidoJson === false) {

                Log::error(
                    'Error al generar JSON de características',
                    [
                        'user_id' => $userId
                    ]
                );

                return response()->json([
                    'error' => 'No se pudo generar el archivo JSON.'
                ], 500);
            }

            $nombreArchivo =
                "datofotos/user_{$userId}_"
                . time()
                . ".json";

            $guardado = Storage::disk('local')->put(
                $nombreArchivo,
                $contenidoJson
            );

            if (!$guardado) {

                Log::error(
                    'Error guardando archivo Datofoto',
                    [
                        'user_id' => $userId,
                        'archivo' => $nombreArchivo
                    ]
                );

                return response()->json([
                    'error' => 'No se pudo guardar el archivo.'
                ], 500);
            }

            $registro = Datofoto::create([
                'user_id' => $userId,
                'archivo_json' => $nombreArchivo,
                'foto_frontal_id' => $fotoFrontal,
                'foto_superior_id' => $fotoSuperior,
                'foto_lateral_izquierda_id' => $fotoIzquierda,
                'foto_lateral_derecha_id' => $fotoDerecha,
            ]);

            if (!$registro) {

                Log::error(
                    'Error creando registro Datofoto',
                    [
                        'user_id' => $userId
                    ]
                );

                return response()->json([
                    'error' => 'No se pudo registrar el análisis.'
                ], 500);
            }

            return response()->json([
                'success' => true,
                'archivo' => $nombreArchivo,
                'registro_id' => $registro->id
            ]);

        } catch (\Exception $e) {

            Log::error(
                'Error en DatofotoController@guardar',
                [
                    'message' => $e->getMessage(),
                    'line' => $e->getLine(),
                    'file' => $e->getFile(),
                    'user_id' => session('usuario_id')
                ]
            );

            return response()->json([
                'error' => 'Ha ocurrido un error interno.'
            ], 500);
        }
    }
}