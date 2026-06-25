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
        try {

            $userId = session('usuario_id');

            if (!$userId) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Sesión no válida.'
                ], 401);
            }

            $datofotos = Datofoto::where('user_id', $userId)
                ->orderBy('created_at', 'desc')
                ->get();

            $cuestionarios = Cuestionario::where('user_id', $userId)
                ->orderBy('created_at', 'desc')
                ->get();

            if ($datofotos->count() < 2 || $cuestionarios->count() < 2) {
                return response()->json([
                    'status' => 'not_enough_data'
                ]);
            }

            $dfNuevo = $datofotos->first();
            $cuestionarioNuevo = $cuestionarios->first();

            if (!Storage::exists($dfNuevo->archivo_json)) {

                return response()->json([
                    'status' => 'error',
                    'message' => 'No existe el archivo de datos fotográficos actual.'
                ], 404);
            }

            if (!Storage::exists($cuestionarioNuevo->archivo_json)) {

                return response()->json([
                    'status' => 'error',
                    'message' => 'No existe el archivo del cuestionario actual.'
                ], 404);
            }

            $jsonNuevo = json_decode(
                Storage::get($dfNuevo->archivo_json),
                true
            );

            if (json_last_error() !== JSON_ERROR_NONE) {

                return response()->json([
                    'status' => 'error',
                    'message' => 'JSON de fotografías actual inválido.'
                ], 500);
            }

            $jsonCuestionarioNuevo = json_decode(
                Storage::get($cuestionarioNuevo->archivo_json),
                true
            );

            if (json_last_error() !== JSON_ERROR_NONE) {

                return response()->json([
                    'status' => 'error',
                    'message' => 'JSON de cuestionario actual inválido.'
                ], 500);
            }

            $datofotosAntiguos = $datofotos->slice(1);
            $cuestionariosAntiguos = $cuestionarios->slice(1);

            foreach ($datofotosAntiguos as $index => $dfAntiguo) {

                if (!isset($cuestionariosAntiguos[$index])) {

                    Log::warning(
                        "No existe cuestionario antiguo para index {$index}"
                    );

                    continue;
                }

                $cuestionarioAntiguo = $cuestionariosAntiguos[$index];

                if ($dfAntiguo->id === $dfNuevo->id) {
                    continue;
                }

                if ($cuestionarioAntiguo->id === $cuestionarioNuevo->id) {
                    continue;
                }

                if (!Storage::exists($dfAntiguo->archivo_json)) {

                    Log::warning('Archivo Datofoto antiguo inexistente', [
                        'datofoto_id' => $dfAntiguo->id
                    ]);

                    continue;
                }

                if (!Storage::exists($cuestionarioAntiguo->archivo_json)) {

                    Log::warning('Archivo Cuestionario antiguo inexistente', [
                        'cuestionario_id' => $cuestionarioAntiguo->id
                    ]);

                    continue;
                }

                $jsonAntiguo = json_decode(
                    Storage::get($dfAntiguo->archivo_json),
                    true
                );

                if (json_last_error() !== JSON_ERROR_NONE) {

                    Log::warning('JSON antiguo de fotografías inválido', [
                        'datofoto_id' => $dfAntiguo->id
                    ]);

                    continue;
                }

                $jsonCuestionarioAntiguo = json_decode(
                    Storage::get($cuestionarioAntiguo->archivo_json),
                    true
                );

                if (json_last_error() !== JSON_ERROR_NONE) {

                    Log::warning('JSON antiguo de cuestionario inválido', [
                        'cuestionario_id' => $cuestionarioAntiguo->id
                    ]);

                    continue;
                }

                $existe = Comparison::where('user_id', $userId)
                    ->where('datofoto_nuevo_id', $dfNuevo->id)
                    ->where('datofoto_antiguo_id', $dfAntiguo->id)
                    ->where('cuestionario_nuevo_id', $cuestionarioNuevo->id)
                    ->where('cuestionario_antiguo_id', $cuestionarioAntiguo->id)
                    ->first();

                if ($existe) {
                    continue;
                }

                $response = Http::timeout(60)->post(
                    'http://capilai-n8n:5678/webhook/comparacion',
                    [
                        'datofoto_nuevo' => $jsonNuevo,
                        'datofoto_antiguo' => $jsonAntiguo,
                        'cuestionario_nuevo' => $jsonCuestionarioNuevo,
                        'cuestionario_antiguo' => $jsonCuestionarioAntiguo
                    ]
                );

                if (!$response->successful()) {

                    Log::error('Error en webhook comparacion', [
                        'status' => $response->status(),
                        'body' => $response->body()
                    ]);

                    continue;
                }

                $data = $response->body();

                $archivoTexto =
                    "analysis/Comparaciones/texto_user_{$userId}_"
                    . time()
                    . "_{$dfAntiguo->id}.txt";

                Storage::put($archivoTexto, $data);

                Comparison::create([
                    'user_id' => $userId,
                    'datofoto_nuevo_id' => $dfNuevo->id,
                    'datofoto_antiguo_id' => $dfAntiguo->id,
                    'cuestionario_nuevo_id' => $cuestionarioNuevo->id,
                    'cuestionario_antiguo_id' => $cuestionarioAntiguo->id,
                    'comparison_text' => $archivoTexto
                ]);
            }

            return response()->json([
                'status' => 'ok'
            ]);

        } catch (\Exception $e) {

            Log::error('Error en ComparisonController@compare', [
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Ha ocurrido un error interno.'
            ], 500);
        }
    }
}