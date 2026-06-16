<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Statamic\Facades\Entry;
use App\Models\Foto;
use Illuminate\Support\Facades\Log;

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
            'slug'   => $slugActual,
        ]);

        if (!$response->successful()) {
            return back()->with('error', 'Error al validar la foto en n8n.');
        }

        $raw = $response->json()['valida'] ?? 'false';
        $raw = ltrim($raw, '=');
        $valida = in_array(strtolower($raw), ['true', '1'], true);

        if (!$valida) {
            return back()->with('error', $response->json()['mensaje'] ?? 'La foto no es válida.');
        }

        $fotoAnterior = Foto::where('user_id', $userId)
                            ->where('slug', $slugActual)
                            ->orderBy('created_at', 'asc')
                            ->first();

        $nombreArchivo = "fotos/user_{$userId}_{$slugActual}_" . time() . ".png";

        if (!$fotoAnterior) {


            Storage::disk('local')->put($nombreArchivo, base64_decode($imageBase64));

            Foto::create([
                'user_id' => $userId,
                'slug'    => $slugActual,
                'base64'  => $nombreArchivo,
                'valida'  => true,
            ]);

        } else {

            $foto1Base64 = base64_encode(Storage::disk('local')->get($fotoAnterior->base64));
            $foto2Base64 = $imageBase64;

            $respAlinear = Http::post('http://capilai-n8n:5678/webhook/alinear-foto', [
                'foto_1' => 'data:image/png;base64,' . $foto1Base64,
                'foto_2' => 'data:image/png;base64,' . $foto2Base64
            ]);

            if (!$respAlinear->successful()) {
                return back()->with('error', 'Error al procesar la foto en n8n.');
            }

            $fotoProcesada = $respAlinear->json()['foto_alineada'] ?? null;

            if (!$fotoProcesada) {
                return back()->with('error', 'n8n no devolvió la foto procesada.');
            }

            $fotoProcesada = ltrim($fotoProcesada, '=');
            $fotoProcesada = preg_replace('/^data:image\/\w+;base64,/', '', $fotoProcesada);
            Storage::disk('local')->put($nombreArchivo, base64_decode($fotoProcesada));

            Log::info($fotoProcesada);

            Storage::disk('local')->put($nombreArchivo, base64_decode($fotoProcesada));

            Foto::create([
                'user_id' => $userId,
                'slug'    => $slugActual,
                'base64'  => $nombreArchivo,
                'valida'  => true,
            ]);
        }

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