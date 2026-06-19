<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Foto;
use App\Models\Comparison;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class ComparisonController extends Controller
{
    public function compare(Request $request)
    {
        $userId = session('usuario_id');

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

            if (!$newPhoto) continue;

            $oldPhotosSameSlug = $otherPhotos->filter(fn($f) => $f->slug === $slug);

            foreach ($oldPhotosSameSlug as $oldPhoto) {

                if ($newPhoto->id === $oldPhoto->id) continue;

                $newBinary = Storage::get($newPhoto->base64);
                $oldBinary = Storage::get($oldPhoto->base64);

                $response = Http::post(env('http://capilai-n8n:5678/webhook/comparacion'), [
                    'photo_a' => base64_encode($newBinary),
                    'photo_b' => base64_encode($oldBinary),
                    'slug' => $slug
                ]);

                $data = $response->json();

                $fileName = "comparison_{$userId}_{$newPhoto->id}_{$oldPhoto->id}_" . time() . ".txt";
                $filePath = "private/analysis/comparaciones/" . $fileName;

                Storage::put($filePath, $data['texto']);

                Comparison::create([
                    'user_id' => $userId,
                    'photo_a_id' => $newPhoto->id,
                    'photo_b_id' => $oldPhoto->id,
                    'comparison_text' => $filePath
                ]);
            }
        }

        return response()->json([
            'status' => 'ok',
            'message' => 'Comparaciones generadas y guardadas correctamente.'
        ]);
    }
}
