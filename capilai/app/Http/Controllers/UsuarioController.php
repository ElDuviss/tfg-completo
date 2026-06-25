<?php

namespace App\Http\Controllers;

use App\Models\Usuario;
use App\Models\Cuestionario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class UsuarioController extends Controller
{
    public function store(Request $request)
    {
        try {

            $request->validate([
                'email' => 'required|email|max:255|unique:usuarios,email',
                'password' => 'required|min:6|max:255',
                'username' => 'required|string|max:100|unique:usuarios,username',
            ], [
                'email.unique' => 'Este correo ya está registrado, prueba con otro.',
                'username.unique' => 'Este nombre de usuario ya está en uso.'
            ]);

            $usuario = Usuario::create([
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'username' => $request->username,
            ]);

            if (!$usuario) {

                Log::error('Error creando usuario', [
                    'email' => $request->email
                ]);

                return redirect()
                    ->back()
                    ->with(
                        'error',
                        'No se pudo crear la cuenta.'
                    );
            }

            return redirect()
                ->back()
                ->with('success', true);

        } catch (\Exception $e) {

            Log::error('Error en UsuarioController@store', [
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ]);

            return redirect()
                ->back()
                ->with(
                    'error',
                    'Ha ocurrido un error inesperado.'
                );
        }
    }

    public function login(Request $request)
    {
        try {

            $request->validate([
                'email' => 'required|email|max:255',
                'password' => 'required|max:255',
            ]);

            $usuario = Usuario::where(
                'email',
                $request->email
            )->first();

            if (!$usuario) {

                return redirect()
                    ->back()
                    ->with(
                        'login_error',
                        'El email no está registrado.'
                    );
            }

            if (
                !Hash::check(
                    $request->password,
                    $usuario->password
                )
            ) {

                return redirect()
                    ->back()
                    ->with(
                        'login_error',
                        'La contraseña es incorrecta.'
                    );
            }

            session([
                'usuario_id' => $usuario->id
            ]);

            $request->session()->regenerate();

            $tieneCuestionario = Cuestionario::where(
                'user_id',
                $usuario->id
            )->exists();

            if ($tieneCuestionario) {
                return redirect('/analysis/menu_analysis');
            }

            return redirect('/questionaire');

        } catch (\Exception $e) {

            Log::error('Error en UsuarioController@login', [
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ]);

            return redirect()
                ->back()
                ->with(
                    'login_error',
                    'Ha ocurrido un error inesperado.'
                );
        }
    }

    public function index()
    {
        try {

            $usuarios = Usuario::all();

            return view('usuarios', [
                'usuarios' => $usuarios
            ]);

        } catch (\Exception $e) {

            Log::error('Error en UsuarioController@index', [
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ]);

            abort(500);
        }
    }
}