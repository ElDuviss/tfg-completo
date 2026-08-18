<?php

namespace App\Http\Controllers;

use App\Models\Comparison;
use App\Models\Datofoto;
use App\Models\Cuestionario;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ComparisonController extends Controller
{
    public function compare()
    {
        try {

            $userId = session('usuario_id');

            Log::info('Inicio ComparisonController@compare', [
                'user_id' => $userId
            ]);

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

            Log::info('Datos encontrados', [
                'datofotos' => $datofotos->count(),
                'cuestionarios' => $cuestionarios->count()
            ]);

            if ($datofotos->count() < 2 || $cuestionarios->count() < 2) {

                Log::warning('No hay suficientes datos para comparar', [
                    'datofotos' => $datofotos->count(),
                    'cuestionarios' => $cuestionarios->count()
                ]);

                return response()->json([
                    'status' => 'not_enough_data'
                ]);
            }

            $dfNuevo = $datofotos->first();
            $cuestionarioNuevo = $cuestionarios->first();

            Log::info('Elementos nuevos seleccionados', [
                'datofoto_id' => $dfNuevo->id,
                'cuestionario_id' => $cuestionarioNuevo->id
            ]);

            $jsonNuevo = $dfNuevo->archivo_json;
            $jsonCuestionarioNuevo = $cuestionarioNuevo->archivo_json;

            Log::info('Tipos originales', [
                'datofoto_nuevo_tipo' => gettype($jsonNuevo),
                'cuestionario_nuevo_tipo' => gettype($jsonCuestionarioNuevo),
            ]);

            if (is_string($jsonNuevo)) {
                $jsonNuevo = json_decode($jsonNuevo, true);
            }

            if (is_string($jsonCuestionarioNuevo)) {
                $jsonCuestionarioNuevo = json_decode($jsonCuestionarioNuevo, true);
            }

            if (!is_array($jsonNuevo)) {

                Log::error('Datofoto nuevo inválido', [
                    'id' => $dfNuevo->id,
                    'tipo' => gettype($jsonNuevo),
                    'contenido' => substr((string)$dfNuevo->archivo_json, 0, 500)
                ]);

                return response()->json([
                    'status' => 'error',
                    'message' => 'Datofoto nuevo inválido.'
                ], 500);
            }

            if (!is_array($jsonCuestionarioNuevo)) {

                Log::error('Cuestionario nuevo inválido', [
                    'id' => $cuestionarioNuevo->id,
                    'tipo' => gettype($jsonCuestionarioNuevo),
                    'contenido' => substr((string)$cuestionarioNuevo->archivo_json, 0, 500)
                ]);

                return response()->json([
                    'status' => 'error',
                    'message' => 'Cuestionario nuevo inválido.'
                ], 500);
            }

            $datofotosAntiguos = $datofotos->slice(1)->values();
            $cuestionariosAntiguos = $cuestionarios->slice(1)->values();

            foreach ($datofotosAntiguos as $index => $dfAntiguo) {

                Log::info('Procesando comparación', [
                    'index' => $index,
                    'datofoto_antiguo_id' => $dfAntiguo->id
                ]);

                if (!isset($cuestionariosAntiguos[$index])) {

                    Log::warning('No existe cuestionario antiguo', [
                        'index' => $index
                    ]);

                    continue;
                }

                $cuestionarioAntiguo = $cuestionariosAntiguos[$index];

                $jsonAntiguo = $dfAntiguo->archivo_json;
                $jsonCuestionarioAntiguo = $cuestionarioAntiguo->archivo_json;

                if (is_string($jsonAntiguo)) {
                    $jsonAntiguo = json_decode($jsonAntiguo, true);
                }

                if (is_string($jsonCuestionarioAntiguo)) {
                    $jsonCuestionarioAntiguo = json_decode($jsonCuestionarioAntiguo, true);
                }

                if (!is_array($jsonAntiguo)) {

                    Log::warning('Datofoto antiguo inválido', [
                        'id' => $dfAntiguo->id,
                        'tipo' => gettype($jsonAntiguo)
                    ]);

                    continue;
                }

                if (!is_array($jsonCuestionarioAntiguo)) {

                    Log::warning('Cuestionario antiguo inválido', [
                        'id' => $cuestionarioAntiguo->id,
                        'tipo' => gettype($jsonCuestionarioAntiguo)
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

                    Log::info('Comparación ya existente', [
                        'comparison_id' => $existe->id
                    ]);

                    continue;
                }

                Log::info('Enviando webhook n8n', [
                    'datofoto_nuevo_id' => $dfNuevo->id,
                    'datofoto_antiguo_id' => $dfAntiguo->id,
                    'cuestionario_nuevo_id' => $cuestionarioNuevo->id,
                    'cuestionario_antiguo_id' => $cuestionarioAntiguo->id
                ]);

                try {

                    $response = Http::timeout(120)->post(
                        'https://n8n-xigf.onrender.com/webhook/comparacion',
                        [
                            'datofoto_nuevo' => $jsonNuevo,
                            'datofoto_antiguo' => $jsonAntiguo,
                            'cuestionario_nuevo' => $jsonCuestionarioNuevo,
                            'cuestionario_antiguo' => $jsonCuestionarioAntiguo
                        ]
                    );

                    Log::info('Respuesta n8n', [
                        'status' => $response->status(),
                        'body' => substr($response->body(), 0, 1000)
                    ]);

                    if (!$response->successful()) {

                        Log::error('Error webhook n8n', [
                            'status' => $response->status(),
                            'body' => $response->body()
                        ]);

                        continue;
                    }

                    $textoGenerado = trim($response->body());

                    if (empty($textoGenerado)) {

                        Log::warning('n8n devolvió texto vacío');

                        continue;
                    }

                    $comparison = Comparison::create([
                        'user_id' => $userId,
                        'datofoto_nuevo_id' => $dfNuevo->id,
                        'datofoto_antiguo_id' => $dfAntiguo->id,
                        'cuestionario_nuevo_id' => $cuestionarioNuevo->id,
                        'cuestionario_antiguo_id' => $cuestionarioAntiguo->id,
                        'comparison_text' => $textoGenerado
                    ]);

                    Log::info('Comparación guardada', [
                        'comparison_id' => $comparison->id
                    ]);

                } catch (\Exception $e) {

                    Log::error('Excepción llamando a n8n', [
                        'message' => $e->getMessage(),
                        'line' => $e->getLine(),
                        'file' => $e->getFile()
                    ]);
                }
            }

            Log::info('Fin de comparación');

            return response()->json([
                'status' => 'ok'
            ]);

        } catch (\Exception $e) {

            Log::error('Error general ComparisonController', [
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