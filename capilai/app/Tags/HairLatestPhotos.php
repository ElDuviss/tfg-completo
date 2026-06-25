<?php

namespace App\Tags;

use Statamic\Tags\Tags;
use App\Models\Foto;
use App\Models\Datofoto;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class HairLatestPhotos extends Tags
{
    public function index()
    {
        try {

            $userId = session('usuario_id');

            if (!$userId) {
                return '<p class="text-gray-500 text-center py-2">
                    No hay sesión activa.
                </p>';
            }

            $slugs = [
                'foto-frontal',
                'foto-superior',
                'foto-lateral-izquierda',
                'foto-lateral-derecha'
            ];

            $html = '';

            foreach ($slugs as $slug) {

                $foto = Foto::where('user_id', $userId)
                    ->where('slug', $slug)
                    ->orderBy('created_at', 'desc')
                    ->first();

                if (!$foto) {

                    $html .= '
                        <p class="text-gray-500 text-center py-2">
                            No existe foto para ' . e($slug) . '.
                        </p>';

                    continue;
                }

                $datofoto = Datofoto::where('user_id', $userId)
                    ->where(function ($q) use ($foto) {
                        $q->where('foto_frontal_id', $foto->id)
                          ->orWhere('foto_superior_id', $foto->id)
                          ->orWhere('foto_lateral_izquierda_id', $foto->id)
                          ->orWhere('foto_lateral_derecha_id', $foto->id);
                    })
                    ->first();

                $datofotoId = $datofoto->id ?? null;

                if (!$foto->base64) {

                    Log::warning('Foto sin ruta base64', [
                        'foto_id' => $foto->id
                    ]);

                    continue;
                }

                $ruta = ltrim($foto->base64, '/');

                if (str_starts_with($ruta, 'private/')) {
                    $ruta = str_replace('private/', '', $ruta);
                }

                if (!Storage::exists($ruta)) {

                    Log::warning('Archivo de foto no encontrado', [
                        'path' => $ruta,
                        'foto_id' => $foto->id
                    ]);

                    $html .= '
                        <p class="text-red-500 text-center py-2">
                            Archivo no encontrado: ' . e($ruta) . '
                        </p>';

                    continue;
                }

                $contenido = Storage::get($ruta);

                if ($contenido === false) {

                    Log::warning('No se pudo leer archivo de foto', [
                        'path' => $ruta
                    ]);

                    continue;
                }

                $base64 = base64_encode($contenido);
                $dataUrl = "data:image/png;base64," . $base64;

                $html .= '
                    <div class="mb-6 flex flex-col items-center"
                         data-slug="' . e($slug) . '"
                         data-datofoto-id="' . e($datofotoId) . '">
                        <img src="' . $dataUrl . '"
                             alt="' . e($slug) . '"
                             class="rounded-lg shadow w-full max-w-sm" />
                    </div>
                ';
            }

            return $html;

        } catch (\Exception $e) {

            Log::error('Error en HairLatestPhotos tag', [
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ]);

            return '<p class="text-red-500 text-center py-2">
                Error al cargar las fotos.
            </p>';
        }
    }
}