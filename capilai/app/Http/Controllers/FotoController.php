<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Statamic\Facades\Entry;
use App\Models\Foto;

class FotoController extends Controller
{
    public function subirFoto(Request $request)
    {
        try {

            $userId = session('usuario_id');

            if (!$userId) {
                return back()->with(
                    'error',
                    'No hay usuario autenticado.'
                );
            }

            if (
                !$request->filled('slug_actual')
            ) {
                return back()->with(
                    'error',
                    'No se recibió el tipo de fotografía.'
                );
            }

            if ($request->foto_capturada) {

                $imageBase64 = str_replace(
                    'data:image/png;base64,',
                    '',
                    $request->foto_capturada
                );

            } elseif ($request->foto_subida_base64) {

                $imageBase64 = preg_replace(
                    '/^data:image\/\w+;base64,/',
                    '',
                    $request->foto_subida_base64
                );

            } else {

                return back()->with(
                    'error',
                    'No se recibió ninguna imagen.'
                );
            }

            $slugActual = $request->slug_actual;

            $response = Http::post(
                'http://capilai-n8n:5678/webhook/validar-foto',
                [
                    'imagen' => 'data:image/png;base64,' . $imageBase64,
                    'slug'   => $slugActual,
                ]
            );

            if (!$response->successful()) {

                Log::error(
                    'Error en validar-foto',
                    [
                        'status' => $response->status(),
                        'body' => $response->body()
                    ]
                );

                return back()->with(
                    'error',
                    'Error al validar la foto en n8n.'
                );
            }

            $raw = $response->json()['valida'] ?? 'false';
            $raw = ltrim($raw, '=');

            $valida = in_array(
                strtolower($raw),
                ['true', '1'],
                true
            );

            if (!$valida) {

                return back()->with(
                    'error',
                    $response->json()['mensaje']
                    ?? 'La foto no es válida.'
                );
            }

            $fotoAnterior = Foto::where('user_id', $userId)
                ->where('slug', $slugActual)
                ->orderBy('created_at', 'asc')
                ->first();

            $nombreArchivo =
                "fotos/user_{$userId}_{$slugActual}_"
                . time()
                . ".png";

            if (!$fotoAnterior) {

                $imagenBinaria = base64_decode(
                    $imageBase64,
                    true
                );

                if ($imagenBinaria === false) {

                    return back()->with(
                        'error',
                        'La imagen recibida es inválida.'
                    );
                }

                $guardado = Storage::disk('local')->put(
                    $nombreArchivo,
                    $imagenBinaria
                );

                if (!$guardado) {

                    return back()->with(
                        'error',
                        'No se pudo guardar la fotografía.'
                    );
                }

                $foto = Foto::create([
                    'user_id' => $userId,
                    'slug'    => $slugActual,
                    'base64'  => $nombreArchivo,
                    'valida'  => true,
                ]);

                if (!$foto) {

                    return back()->with(
                        'error',
                        'No se pudo registrar la fotografía.'
                    );
                }

            } else {

                if (
                    !Storage::disk('local')->exists(
                        $fotoAnterior->base64
                    )
                ) {

                    return back()->with(
                        'error',
                        'La fotografía anterior no existe.'
                    );
                }

                $foto1Base64 = base64_encode(
                    Storage::disk('local')->get(
                        $fotoAnterior->base64
                    )
                );

                $foto2Base64 = $imageBase64;

                $respAlinear = Http::post(
                    'http://capilai-n8n:5678/webhook/alinear-foto',
                    [
                        'foto_1' =>
                            'data:image/png;base64,' . $foto1Base64,
                        'foto_2' =>
                            'data:image/png;base64,' . $foto2Base64
                    ]
                );

                if (!$respAlinear->successful()) {

                    Log::error(
                        'Error en alinear-foto',
                        [
                            'status' => $respAlinear->status(),
                            'body' => $respAlinear->body()
                        ]
                    );

                    return back()->with(
                        'error',
                        'Error al procesar la foto en n8n.'
                    );
                }

                $fotoProcesada =
                    $respAlinear->json()['foto_alineada']
                    ?? null;

                if (!$fotoProcesada) {

                    return back()->with(
                        'error',
                        'n8n no devolvió la foto procesada.'
                    );
                }

                $fotoProcesada = ltrim(
                    $fotoProcesada,
                    '='
                );

                $fotoProcesada = preg_replace(
                    '/^data:image\/\w+;base64,/',
                    '',
                    $fotoProcesada
                );

                $imagenBinaria = base64_decode(
                    $fotoProcesada,
                    true
                );

                if ($imagenBinaria === false) {

                    return back()->with(
                        'error',
                        'La imagen procesada es inválida.'
                    );
                }

                $guardado = Storage::disk('local')->put(
                    $nombreArchivo,
                    $imagenBinaria
                );

                if (!$guardado) {

                    return back()->with(
                        'error',
                        'No se pudo guardar la fotografía procesada.'
                    );
                }

                $foto = Foto::create([
                    'user_id' => $userId,
                    'slug'    => $slugActual,
                    'base64'  => $nombreArchivo,
                    'valida'  => true,
                ]);

                if (!$foto) {

                    return back()->with(
                        'error',
                        'No se pudo registrar la fotografía.'
                    );
                }
            }

            $entry = Entry::query()
                ->where('collection', 'photos')
                ->where('slug', $slugActual)
                ->first();

            if ($entry) {

                $entry->set('valida', true);
                $entry->save();
            }

            return redirect('/photos/menu');

        } catch (\Exception $e) {

            Log::error(
                'Error en FotoController@subirFoto',
                [
                    'message' => $e->getMessage(),
                    'line' => $e->getLine(),
                    'file' => $e->getFile(),
                    'user_id' => session('usuario_id')
                ]
            );

            return back()->with(
                'error',
                'Ha ocurrido un error inesperado.'
            );
        }
    }
}