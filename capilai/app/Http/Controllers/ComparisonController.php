<?php

namespace App\Http\Controllers;

use App\Models\Comparison;
use App\Models\Datofoto;
use App\Models\Cuestionario;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class ComparisonController extends Controller
{
    public function compare()
    {
        $userId = session('usuario_id');

        $datofotos = Datofoto::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->get();

        $cuestionarios = Cuestionario::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->get();

        if ($datofotos->count() < 2 || $cuestionarios->count() < 2) {
            return response()->json(['status' => 'not_enough_data']);
        }

        $dfNuevo = $datofotos->first();
        $jsonNuevo = json_decode(Storage::get($dfNuevo->archivo_json), true);

        $cuestionarioNuevo = $cuestionarios->first();
        $jsonCuestionarioNuevo = json_decode(Storage::get($cuestionarioNuevo->archivo_json), true);

        $datofotosAntiguos = $datofotos->slice(1);
        $cuestionariosAntiguos = $cuestionarios->slice(1);

        foreach ($datofotosAntiguos as $index => $dfAntiguo) {

            if (!isset($cuestionariosAntiguos[$index])) {
                Log::warning("No existe cuestionario antiguo para index $index");
                continue;
            }

            $cuestionarioAntiguo = $cuestionariosAntiguos[$index];

            if ($dfAntiguo->id === $dfNuevo->id) continue;
            if ($cuestionarioAntiguo->id === $cuestionarioNuevo->id) continue;

            $jsonAntiguo = json_decode(Storage::get($dfAntiguo->archivo_json), true);
            $jsonCuestionarioAntiguo = json_decode(Storage::get($cuestionarioAntiguo->archivo_json), true);

            $existe = Comparison::where('user_id', $userId)
                ->where('datofoto_nuevo_id', $dfNuevo->id)
                ->where('datofoto_antiguo_id', $dfAntiguo->id)
                ->where('cuestionario_nuevo_id', $cuestionarioNuevo->id)
                ->where('cuestionario_antiguo_id', $cuestionarioAntiguo->id)
                ->first();

            if ($existe) continue;

            $response = Http::post('http://capilai-n8n:5678/webhook/comparacion', [
                'datofoto_nuevo' => $jsonNuevo,
                'datofoto_antiguo' => $jsonAntiguo,
                'cuestionario_nuevo' => $jsonCuestionarioNuevo,
                'cuestionario_antiguo' => $jsonCuestionarioAntiguo
            ]);

            $data = $response->body();

            $archivoTexto = "analysis/Comparaciones/texto_user_{$userId}_" . time() . ".txt";

            Storage::put($archivoTexto, $data);

            $comparison = Comparison::create([
                'user_id' => $userId,
                'datofoto_nuevo_id' => $dfNuevo->id,
                'datofoto_antiguo_id' => $dfAntiguo->id,
                'cuestionario_nuevo_id' => $cuestionarioNuevo->id,
                'cuestionario_antiguo_id' => $cuestionarioAntiguo->id,
                'comparison_text' => $archivoTexto
            ]);
        }

        return response()->json(['status' => 'ok']);
    }
}