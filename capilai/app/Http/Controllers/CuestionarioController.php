<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\Cuestionario;
use App\Models\Usuario;
use Statamic\Facades\Entry;

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

        Cuestionario::updateOrCreate(
            ['user_id' => $user->id],
            ['archivo_json' => $filename]
        );

        $entries = Entry::query()
            ->where('collection', 'photos')
            ->get();

        foreach ($entries as $entry) {
            $entry->set('valida', false);
            $entry->save();
        }

        return redirect('/photos/menu');
    }
}