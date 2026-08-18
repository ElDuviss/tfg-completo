<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Cuestionario;
use App\Models\Datofoto;
use App\Models\Analysis;
use App\Models\Usuario;

class AnalysisController extends Controller
{
    public function storeJson(Request $request)
    {
        $request->validate([
            'user_id' => 'required|integer|exists:usuarios,id',
            'slug'    => 'required|string|max:255',
        ]);

        try {

            $userId = $request->user_id;
            $slug = $request->slug;

            Log::info('Inicio generación análisis', [
                'user_id' => $userId,
                'slug' => $slug
            ]);

            $cuestionario = Cuestionario::where('user_id', $userId)
                ->latest()
                ->first();

            if (!$cuestionario) {

                return response()->json([
                    'success' => false,
                    'message' => 'No existe cuestionario para este usuario.'
                ], 404);
            }

            $datosCuestionario = $cuestionario->archivo_json;

            if (is_string($datosCuestionario)) {

                $datosCuestionario = json_decode($datosCuestionario, true);
            }

            if (!is_array($datosCuestionario)) {

                Log::error('Cuestionario JSON inválido', [
                    'valor' => $datosCuestionario
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'El cuestionario no contiene JSON válido.'
                ], 500);
            }

            $datofoto = Datofoto::where('user_id', $userId)
                ->latest()
                ->first();

            if (!$datofoto) {

                return response()->json([
                    'success' => false,
                    'message' => 'No existen datos de fotografías.'
                ], 404);
            }

            $features = $datofoto->archivo_json;

            if (is_string($features)) {

                $features = json_decode($features, true);
            }

            if (!is_array($features)) {

                Log::error('Features inválidos', [
                    'valor' => $features
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Las características de imagen no son válidas.'
                ], 500);
            }

            $analysisPrevio = Analysis::where('user_id', $userId)
                ->where('type', $slug)
                ->where('cuestionario_id', $cuestionario->id)
                ->where('datofoto_id', $datofoto->id)
                ->latest()
                ->first();

            if ($analysisPrevio && !empty($analysisPrevio->ai_response)) {

                return response()->json([
                    'success' => true,
                    'texto' => $analysisPrevio->ai_response
                ]);
            }

            Http::timeout(60)->post(
                'https://capilai-n8n.onrender.com/webhook/enviar-datos',
                [
                    'slug' => $slug
                ]
            );

            Http::timeout(60)->post(
                'https://capilai-n8n.onrender.com/webhook/datos-imagenes',
                [
                    'features_globales' => $features
                ]
            );

            $respuesta = Http::timeout(120)->post(
                'https://capilai-n8n.onrender.com/webhook/enviar-cuestionario',
                $datosCuestionario
            );

            if (!$respuesta->successful()) {

                Log::error('Error respuesta n8n', [
                    'status' => $respuesta->status(),
                    'body' => $respuesta->body()
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'n8n no pudo generar el análisis.'
                ], 500);
            }

            $textoGenerado = $respuesta->body();

            if (empty($textoGenerado)) {

                return response()->json([
                    'success' => false,
                    'message' => 'n8n devolvió una respuesta vacía.'
                ], 500);
            }

            $analysis = Analysis::create([

                'user_id' => $userId,

                'type' => $slug,

                'cuestionario_id' => $cuestionario->id,

                'datofoto_id' => $datofoto->id,

                'ai_response' => $textoGenerado,

            ]);

            Log::info('Analysis creado correctamente', [
                'analysis_id' => $analysis->id
            ]);

            return response()->json([

                'success' => true,

                'texto' => $textoGenerado

            ]);

        } catch (\Exception $e) {

            Log::error('Error en AnalysisController@storeJson', [

                'message' => $e->getMessage(),

                'line' => $e->getLine(),

                'file' => $e->getFile(),

            ]);

            return response()->json([

                'success' => false,

                'message' => 'Ha ocurrido un error interno.'

            ], 500);
        }
    }

    public function destroyAccount(Request $request)
    {

        $userId = session('usuario_id');

        if (!$userId) {

            return redirect('/')
                ->with('error', 'No hay ninguna sesión activa.');
        }

        $usuario = Usuario::find($userId);

        if (!$usuario) {

            return redirect('/')
                ->with('error', 'El usuario no existe.');
        }

        $usuario->delete();

        auth()->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/')
            ->with('success', 'Cuenta eliminada correctamente.');
    }
}