<?php

namespace App\Tags;

use Statamic\Tags\Tags;
use App\Models\Foto;
use App\Models\Datofoto;
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

                Log::info('Buscando foto', [
                    'user_id' => $userId,
                    'slug' => $slug
                ]);

                $foto = Foto::where('user_id', $userId)
                    ->where('slug', $slug)
                    ->orderBy('created_at', 'desc')
                    ->first();

                if (!$foto) {

                    Log::warning('No se encontró foto', [
                        'user_id' => $userId,
                        'slug' => $slug
                    ]);

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

                if (!$datofoto) {
                    Log::warning('No existe Datofoto asociado', [
                        'foto_id' => $foto->id,
                        'slug' => $slug
                    ]);
                }

                $datofotoId = $datofoto->id ?? null;

                if (empty($foto->base64)) {

                    Log::warning('Foto sin base64 en BD', [
                        'foto_id' => $foto->id
                    ]);

                    continue;
                }

                Log::info('Foto encontrada', [
                    'foto_id' => $foto->id,
                    'slug' => $slug,
                    'base64_length' => strlen($foto->base64)
                ]);

                $base64 = trim($foto->base64);

                // Corrige imágenes guardadas como:
                // =data:image/png;base64,...
                $base64 = ltrim($base64, '=');

                if (
                    str_starts_with($base64, 'data:image/png;base64,') ||
                    str_starts_with($base64, 'data:image/jpeg;base64,') ||
                    str_starts_with($base64, 'data:image/jpg;base64,') ||
                    str_starts_with($base64, 'data:image/webp;base64,')
                ) {
                    $dataUrl = $base64;
                } else {
                    $dataUrl = 'data:image/png;base64,' . $base64;
                }

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