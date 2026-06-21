<?php

namespace App\Http\Controllers;

use App\Models\Comparison;
use App\Models\Datofoto;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class ComparisonController extends Controller
{
    public function compare()
    {
        $userId = session('usuario_id');

        $datofotos = Datofoto::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->get();

        if ($datofotos->count() < 2) {
            return;
        }

        $dfNuevo = $datofotos->first();
        $jsonNuevo = json_decode(Storage::get($dfNuevo->archivo_json), true);

        $datofotosAntiguos = $datofotos->slice(1);

        foreach ($datofotosAntiguos as $dfAntiguo) {

            if ($dfAntiguo->id === $dfNuevo->id) {
                continue;
            }

            $jsonAntiguo = json_decode(Storage::get($dfAntiguo->archivo_json), true);

            $existe = Comparison::where('user_id', $userId)
                ->where('datofoto_nuevo_id', $dfNuevo->id)
                ->where('datofoto_antiguo_id', $dfAntiguo->id)
                ->first();

            if ($existe) {
                continue;
            }

            $response = Http::post('http://capilai-n8n:5678/webhook/comparacion', [
                'datofoto_nuevo' => $jsonNuevo,
                'datofoto_antiguo' => $jsonAntiguo
            ]);

            $data = $response->json();

            $archivoTexto = "analysis/Comparaciones/texto_user_{$userId}_" . time() . ".txt";
            Storage::put($archivoTexto, $data['texto']);

            Comparison::create([
                'user_id' => $userId,
                'datofoto_nuevo_id' => $dfNuevo->id,
                'datofoto_antiguo_id' => $dfAntiguo->id,
                'comparison_text' => $archivoTexto
            ]);
        }
    }
}