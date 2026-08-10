<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Cuestionario;
use App\Models\Usuario;
use Statamic\Facades\Entry;

class CuestionarioController extends Controller
{
    public function guardar(Request $request)
    {
        try {

            $userId = session('usuario_id');

            if (!$userId) {
                return back()->with('error', 'No hay usuario autenticado.');
            }

            $user = Usuario::find($userId);

            if (!$user) {
                return back()->with('error', 'Usuario no encontrado en la base de datos.');
            }

            $data = $request->except('_token');

            $jsonData = json_encode($data, JSON_UNESCAPED_UNICODE);

            if ($jsonData === false) {

                Log::error('Error al generar JSON del cuestionario', [
                    'user_id' => $userId,
                    'json_error' => json_last_error_msg(),
                ]);

                return back()->with('error', 'No se pudo procesar el cuestionario.');
            }

            $cuestionario = Cuestionario::create([
                'user_id'      => $user->id,
                'archivo_json' => $jsonData,
            ]);

            if (!$cuestionario) {

                Log::error('Error creando registro Cuestionario', [
                    'user_id' => $userId
                ]);

                return back()->with('error', 'No se pudo registrar el cuestionario.');
            }

            $entries = Entry::query()
                ->where('collection', 'photos')
                ->get();

            foreach ($entries as $entry) {
                $entry->set('valida', false);
                $entry->save();
            }

            return redirect('/photos/menu');

        } catch (\Exception $e) {

            Log::error('Error en CuestionarioController@guardar', [
                'message' => $e->getMessage(),
                'line'    => $e->getLine(),
                'file'    => $e->getFile(),
                'user_id' => session('usuario_id'),
            ]);

            return back()->with('error', 'Ha ocurrido un error inesperado.');
        }
    }
}