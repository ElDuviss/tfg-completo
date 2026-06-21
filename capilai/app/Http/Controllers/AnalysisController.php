<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\Cuestionario;
use App\Models\Datofoto;
use App\Models\Analysis;
use App\Models\Usuario;
use App\Models\ChatMessage;
use Illuminate\Support\Facades\Http;

class AnalysisController extends Controller
{
    public function storeJson(Request $request)
    {
        $request->validate([
            'user_id' => 'required|integer',
            'slug'    => 'required|string',
        ]);

        $userId = $request->user_id;
        $slug   = $request->slug;

        $cuestionario = Cuestionario::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->firstOrFail();

        $contenidoCuestionario = Storage::get($cuestionario->archivo_json);
        $datosCuestionario = json_decode($contenidoCuestionario, true);

        $datofoto = Datofoto::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->firstOrFail();

        $contenidoFotos = Storage::get($datofoto->archivo_json);
        $features = json_decode($contenidoFotos, true);

        $analysisPrevio = Analysis::where('user_id', $userId)
            ->where('type', $slug)
            ->where('cuestionario_id', $cuestionario->id)
            ->where('datofoto_id', $datofoto->id)
            ->latest()
            ->first();

        $Generar = true;
        $textoGenerado = null;

        if ($analysisPrevio) {
            $textoGenerado = Storage::get($analysisPrevio->ai_response);
            $Generar = false;
        }

        if ($Generar) {
            $analisisCoincidentes = Analysis::where('type', $slug)
                ->where('cuestionario_id', $cuestionario->id)
                ->where('datofoto_id', $datofoto->id)
                ->get();

            foreach ($analisisCoincidentes as $analisis) {
                $textoGenerado = Storage::get($analisis->ai_response);

                Analysis::create([
                    'user_id'         => $userId,
                    'type'            => $slug,
                    'cuestionario_id' => $cuestionario->id,
                    'datofoto_id'     => $datofoto->id,
                    'ai_response'     => $analisis->ai_response,
                ]);

                $Generar = false;
                break;
            }
        }

        if ($Generar) {

            Http::post('http://capilai-n8n:5678/webhook/enviar-datos', [
                'slug' => $slug,
            ]);

            Http::post('http://capilai-n8n:5678/webhook/datos-imagenes', [
                'features_globales' => $features
            ]);

            $respuesta = Http::post('http://capilai-n8n:5678/webhook/enviar-cuestionario', $datosCuestionario);
            $textoGenerado = $respuesta->body();

            $archivoTexto = "analysis/Respuestas/texto_user_{$userId}_{$slug}_" . time() . ".txt";
            Storage::put($archivoTexto, $textoGenerado);

            Analysis::create([
                'user_id'         => $userId,
                'type'            => $slug,
                'cuestionario_id' => $cuestionario->id,
                'datofoto_id'     => $datofoto->id,
                'ai_response'     => $archivoTexto,
            ]);
        }

        return response()->json([
            'success' => true,
            'texto'   => $textoGenerado
        ]);
    }

    public function destroyAccount(Request $request)
    {
        $user = session('usuario_id');

        if (!$user) {
            return redirect('/')->with('error', 'No hay ninguna sesión activa.');
        }

        Usuario::where('id', $user)->delete();

        auth()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/')->with('success', 'Cuenta eliminada correctamente.');
    }

}