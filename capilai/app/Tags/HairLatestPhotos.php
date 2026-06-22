<?php

namespace App\Tags;

use Statamic\Tags\Tags;
use App\Models\Foto;
use App\Models\Datofoto;
use Illuminate\Support\Facades\Storage;

class HairLatestPhotos extends Tags
{
    public function index()
    {
        $userId = session('usuario_id');

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
                $html .= '<p class="text-gray-500 text-center py-2">No existe foto para '.$slug.'.</p>';
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

            $datofotoId = $datofoto ? $datofoto->id : null;

            $ruta = ltrim($foto->base64, '/');

            if (str_starts_with($ruta, 'private/')) {
                $ruta = str_replace('private/', '', $ruta);
            }

            if (!Storage::exists($ruta)) {
                $html .= '<p class="text-red-500 text-center py-2">Archivo no encontrado: '.$ruta.'</p>';
                continue;
            }

            $contenido = Storage::get($ruta);
            $base64 = base64_encode($contenido);
            $dataUrl = "data:image/png;base64," . $base64;

            $html .= '
                <div class="mb-6 flex flex-col items-center"
                     data-slug="'.$slug.'"
                     data-datofoto-id="'.$datofotoId.'">
                    <img src="'.$dataUrl.'" alt="'.$slug.'" class="rounded-lg shadow w-full max-w-sm" />
                </div>
            ';
        }

        return $html;
    }
}