<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Statamic\Facades\Entry;
use App\Models\Foto;

class FotoController extends Controller
{
    public function subirFoto(Request $request)
    {
        $userId = session('usuario_id');

        if (! $userId) {
            return back()->with('error', 'No hay usuario autenticado.');
        }

        if ($request->foto_capturada) {
            $imageBase64 = str_replace('data:image/png;base64,', '', $request->foto_capturada);
        } elseif ($request->foto_subida_base64) {
            $imageBase64 = preg_replace('/^data:image\/\w+;base64,/', '', $request->foto_subida_base64);
        } else {
            return back()->with('error', 'No se recibió ninguna imagen.');
        }

        $slugActual = $request->slug_actual;

        $response = Http::post('http://capilai-n8n:5678/webhook/validar-foto', [
            'imagen' => 'data:image/png;base64,' . $imageBase64,
            'slug' => $slugActual,
        ]);

        $raw = $response->json()['valida'];
        $raw = ltrim($raw, '=');
        $valida = in_array(strtolower($raw), ['true', '1'], true);

        if (!$valida) {
            return back()->with('error', $response->json()['mensaje']);
        }

        $nombreArchivo = "fotos/user_{$userId}_{$slugActual}_" . time() . ".png";
        Storage::disk('local')->put($nombreArchivo, base64_decode($imageBase64));

        Foto::updateOrCreate(
            [
                'user_id' => $userId,
                'slug' => $slugActual,
            ],
            [
                'base64' => $nombreArchivo,
                'valida' => true,
            ]
        );

        $prefijo = "fotos/user_{$userId}_{$slugActual}_";
        $archivos = Storage::disk('local')->files('fotos');

        foreach ($archivos as $archivo) {
            if (str_starts_with($archivo, $prefijo) && $archivo !== $nombreArchivo) {
                Storage::disk('local')->delete($archivo);
            }
        }

        // Actualizar Statamic
        $entry = Entry::query()
            ->where('collection', 'photos')
            ->where('slug', $slugActual)
            ->first();

        if ($entry) {
            $entry->set('valida', true);
            $entry->save();
        }

        return redirect('/photos/menu');
    }
}