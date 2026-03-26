<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\Cuestionario;
use App\Models\Usuario;

class CuestionarioController extends Controller
{
    public function guardar(Request $request)
    {
        $userId = session('usuario_id');

        if (! $userId) {
            return back()->with('error', 'No hay usuario autenticado.');
        }

        $user = Usuario::find($userId);

        if (! $user) {
            return back()->with('error', 'Usuario no encontrado en la base de datos.');
        }

        $data = $request->except('_token');

        $filename = 'cuestionarios/' . time() . '.json';
        Storage::disk('local')->put($filename, json_encode($data, JSON_PRETTY_PRINT));

        Cuestionario::create([
            'user_id' => $user->id,
            'archivo_json' => $filename,
        ]);

        return redirect('/photos/foto-frontal');
    }
}