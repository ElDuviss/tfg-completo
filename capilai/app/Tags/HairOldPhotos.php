<?php

namespace App\Tags;

use Statamic\Tags\Tags;
use App\Models\Foto;
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

        foreach ($slugs as $slug) {

            $fotos = Foto::where('user_id', $userId)
                ->where('slug', $slug)
                ->orderBy('created_at', 'desc')
                ->get();

            if ($fotos->count() <= 1) continue;

            $fotosAntiguas = $fotos->slice(1);

            foreach ($fotosAntiguas as $foto) {

                $ruta = ltrim($foto->base64, '/');
                $ruta = str_replace(['private/', 'app/'], '', $ruta);

                if (Storage::exists($ruta)) {
                    $contenido = Storage::get($ruta);
                } elseif (Storage::disk('public')->exists($ruta)) {
                    $contenido = Storage::disk('public')->get($ruta);
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
                         data-slug="'.$slug.'">
                        <img src="'.$dataUrl.'" alt="'.$slug.'" class="rounded-lg shadow w-full max-w-sm" />
                    </div>
                ';
            }
        }

        return $html;
    }
}