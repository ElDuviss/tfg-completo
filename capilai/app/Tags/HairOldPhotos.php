<?php

namespace App\Tags;

use Statamic\Tags\Tags;
use App\Models\Foto;
use App\Models\Datofoto;
use Illuminate\Support\Facades\Storage;

class HairOldPhotos extends Tags
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

        // 🔥 1. Buscar la foto más nueva de cada slug
        $idsMasNuevas = [];

        foreach ($slugs as $slug) {
            $fotoNueva = Foto::where('user_id', $userId)
                ->where('slug', $slug)
                ->orderBy('created_at', 'desc')
                ->first();

            if ($fotoNueva) {
                $idsMasNuevas[] = $fotoNueva->id;
            }
        }

        // 🔥 2. Ahora recorremos todas las fotos antiguas por slug
        foreach ($slugs as $slug) {

            $fotos = Foto::where('user_id', $userId)
                ->where('slug', $slug)
                ->orderBy('created_at', 'desc')
                ->get();

            foreach ($fotos as $foto) {

                // ❌ 3. Si esta foto es una de las 4 más nuevas → NO SE MUESTRA
                if (in_array($foto->id, $idsMasNuevas)) {
                    continue;
                }

                // 🔥 4. Buscar el datofoto al que pertenece esta foto
                $datofoto = Datofoto::where('user_id', $userId)
                    ->where(function ($q) use ($foto) {
                        $q->where('foto_frontal_id', $foto->id)
                          ->orWhere('foto_superior_id', $foto->id)
                          ->orWhere('foto_lateral_izquierda_id', $foto->id)
                          ->orWhere('foto_lateral_derecha_id', $foto->id);
                    })
                    ->first();

                $datofotoId = $datofoto ? $datofoto->id : null;

                // Cargar imagen
                $ruta = ltrim($foto->base64, '/');
                $ruta = str_replace(['private/', 'app/'], '', $ruta);

                if (Storage::exists($ruta)) {
                    $contenido = Storage::get($ruta);
                } elseif (Storage::disk('public')->exists($ruta)) {
                    $contenido = Storage::disk('public')->get($ruta);
                } else {
                    continue;
                }

                $base64 = base64_encode($contenido);
                $dataUrl = "data:image/png;base64," . $base64;

                $fecha = $foto->created_at->format('d/m/Y');
                $hora = $foto->created_at->format('H:i:s');
                $fechaCompleta = $foto->created_at->format('Y-m-d H:i:s');

                $html .= '
                    <div class="mb-6 flex flex-col items-center"
                         data-fecha="'.$fecha.'"
                         data-hora="'.$hora.'"
                         data-fecha-completa="'.$fechaCompleta.'"
                         data-slug="'.$slug.'"
                         data-datofoto-id="'.$datofotoId.'">
                        <img src="'.$dataUrl.'" alt="'.$slug.'" class="rounded-lg shadow w-full max-w-sm" />
                    </div>
                ';
            }
        }

        return $html;
    }
}