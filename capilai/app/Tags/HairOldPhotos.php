<?php

namespace App\Tags;

use Statamic\Tags\Tags;
use App\Models\Foto;
use App\Models\Datofoto;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class HairOldPhotos extends Tags
{
    public function index()
    {
        try {

            $userId = session('usuario_id');

            if (!$userId) {
                return '<p class="text-gray-500 text-center py-3">
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

            $fotos = Foto::where('user_id', $userId)
                ->orderBy('created_at', 'desc')
                ->get()
                ->groupBy('slug');

            $idsMasNuevas = [];

            foreach ($slugs as $slug) {
                $fotoNueva = $fotos[$slug]->first() ?? null;

                if ($fotoNueva) {
                    $idsMasNuevas[] = $fotoNueva->id;
                }
            }

            foreach ($slugs as $slug) {

                $fotosSlug = $fotos[$slug] ?? collect();

                if ($fotosSlug->isEmpty()) {
                    continue;
                }

                foreach ($fotosSlug as $foto) {

                    if (in_array($foto->id, $idsMasNuevas)) {
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
                        Log::warning('Foto sin path base64', [
                            'foto_id' => $foto->id
                        ]);
                        continue;
                    }

                    $ruta = ltrim($foto->base64, '/');
                    $ruta = str_replace(['private/', 'app/'], '', $ruta);

                    if (Storage::exists($ruta)) {
                        $contenido = Storage::get($ruta);
                    } elseif (Storage::disk('public')->exists($ruta)) {
                        $contenido = Storage::disk('public')->get($ruta);
                    } else {

                        Log::warning('Archivo no encontrado', [
                            'path' => $ruta,
                            'foto_id' => $foto->id
                        ]);

                        continue;
                    }

                    if ($contenido === false) {
                        continue;
                    }

                    $base64 = base64_encode($contenido);
                    $dataUrl = "data:image/png;base64," . $base64;

                    $fecha = optional($foto->created_at)->format('d/m/Y');
                    $hora = optional($foto->created_at)->format('H:i:s');
                    $fechaCompleta = optional($foto->created_at)->format('Y-m-d H:i:s');

                    $html .= '
                        <div class="mb-6 flex flex-col items-center"
                             data-fecha="' . e($fecha) . '"
                             data-hora="' . e($hora) . '"
                             data-fecha-completa="' . e($fechaCompleta) . '"
                             data-slug="' . e($slug) . '"
                             data-datofoto-id="' . e($datofotoId) . '">
                            <img src="' . $dataUrl . '"
                                 alt="' . e($slug) . '"
                                 class="rounded-lg shadow w-full max-w-sm" />
                        </div>
                    ';
                }
            }

            return $html;

        } catch (\Exception $e) {

            Log::error('Error en HairOldPhotos tag', [
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ]);

            return '<p class="text-red-500 text-center py-3">
                Error al cargar fotos antiguas.
            </p>';
        }
    }
}