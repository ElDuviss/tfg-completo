<?php

namespace App\Http\Controllers;

use App\Models\Foto;
use App\Models\Comparison;
use App\Models\Datofoto;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class ComparisonController extends Controller
{
    public function compare()
    {
        $userId = session('usuario_id');

        $datosFotos = Datofoto::where('user_id', $userId)->get();

        $jsonData = [];

        foreach ($datosFotos as $df) {
            if (Storage::exists($df->archivo_json)) {
                $contenido = Storage::get($df->archivo_json);
                $jsonData[] = json_decode($contenido, true);
            }
        }

        $slugs = [
            'foto-frontal',
            'foto-superior',
            'foto-lateral-izquierda',
            'foto-lateral-derecha'
        ];

        $latestPhotos = [];
        foreach ($slugs as $slug) {
            $latestPhotos[$slug] = Foto::where('user_id', $userId)
                ->where('slug', $slug)
                ->orderBy('created_at', 'desc')
                ->first();
        }

        $latestIds = collect($latestPhotos)->filter()->pluck('id')->toArray();

        $otherPhotos = Foto::where('user_id', $userId)
            ->whereNotIn('id', $latestIds)
            ->orderBy('created_at', 'desc')
            ->get();

        foreach ($latestPhotos as $slug => $newPhoto) {

            if (!$newPhoto) {
                continue;
            }

            $oldPhotosSameSlug = $otherPhotos->filter(fn($f) => $f->slug === $slug);

            foreach ($oldPhotosSameSlug as $oldPhoto) {

                if ($newPhoto->id === $oldPhoto->id) continue;

                $existe = Comparison::where('user_id', $userId)
                    ->where('photo_a_id', $newPhoto->id)
                    ->where('photo_b_id', $oldPhoto->id)
                    ->first();

                if ($existe) {
                    continue;
                }

                $newBinary = Storage::get($newPhoto->base64);
                $oldBinary = Storage::get($oldPhoto->base64);

                $response = Http::post('http://capilai-n8n:5678/webhook/comparacion', [
                    'photo_a' => base64_encode($newBinary),
                    'photo_b' => base64_encode($oldBinary),
                    'slug' => $slug,
                    'datos_fotos' => $jsonData
                ]);

                $data = $response->json();

                $archivoTexto = "analysis/Respuestas/texto_user_{$userId}_{$slug}_" . time() . ".txt";
                Storage::put($archivoTexto, $data['texto']);

                Comparison::create([
                    'user_id' => $userId,
                    'photo_a_id' => $newPhoto->id,
                    'photo_b_id' => $oldPhoto->id,
                    'comparison_text' => $archivoTexto
                ]);
            }
        }
    }
}