<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use Statamic\Facades\Entry;

class FotoController extends Controller
{
    public function subirFoto(Request $request)
    {
        $userId = session('usuario_id');

        if (! $userId) {
            return back()->with('error', 'No hay usuario autenticado.');
        }

        $filename = null;
        $imageBase64 = null;

        // FOTO CAPTURADA DESDE LA CÁMARA
        if ($request->foto_capturada) {
            $image = str_replace('data:image/png;base64,', '', $request->foto_capturada);
            $image = str_replace(' ', '+', $image);

            $imageBase64 = $image;

            $filename = 'foto_' . time() . '.png';
            Storage::disk('public')->put('fotos/' . $filename, base64_decode($image));
        }

        // FOTO SUBIDA DESDE ARCHIVO
        if ($request->foto_subida_base64) {
            $image = preg_replace('/^data:image\/\w+;base64,/', '', $request->foto_subida_base64);

            $imageBase64 = $image;

            $filename = 'foto_' . time() . '.png';
            Storage::disk('public')->put('fotos/' . $filename, base64_decode($image));
        }

        // SLUG ACTUAL (frontal, lateral, etc.)
        $slugActual = $request->slug_actual;

        // 🔥 VALIDACIÓN CON N8N → JAVA
        $response = Http::post('http://n8n:5678/webhook/validar-foto', [
            'imagen' => $imageBase64,
            'slug' => $slugActual,
        ]);

        dd($response->json());

        if (!$response->json()['valida']) {
            return back()->with('error', $response->json()['mensaje']);
        }

        // ORDEN DE FOTOS
        $orden = [
            'foto-frontal',
            'foto-lateral-izquierda',
            'foto-lateral-derecha',
            'foto-superior',
        ];

        $pos = array_search($slugActual, $orden);
        $nextSlug = $pos !== false && isset($orden[$pos + 1]) ? $orden[$pos + 1] : null;

        if ($nextSlug) {
            $nextEntry = Entry::query()
                ->where('collection', 'photos')
                ->where('slug', $nextSlug)
                ->first();

            if ($nextEntry) {
                return redirect($nextEntry->url());
            }
        }

        return redirect('/final');
    }
}