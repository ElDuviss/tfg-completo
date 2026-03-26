<?php

namespace App\Http\Controllers;

use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UsuarioController extends Controller
{
    public function store(Request $request)
    {

        $request->validate([
            'email' => 'required|email|unique:usuarios,email',
            'password' => 'required|min:6',
            'username' => 'required',
        ], [
            'email.unique' => 'Este correo ya esta registrado, prueba con otro.'
        ]);

        $usuario = Usuario::create([
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'username' => $request->username,
        ]);

        return redirect()->back()->with('success', true);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $usuario = Usuario::where('email', $request->email)->first();

        if (! $usuario) {
            return redirect()->back()->with('login_error', 'El email no está registrado.');
        }

        if (! Hash::check($request->password, $usuario->password)) {
            return redirect()->back()->with('login_error', 'La contraseña es incorrecta.');
        }

        session(['usuario_id' => $usuario->id]);

        return redirect('/questionaire');
    }

    public function index()
    {
        $usuarios = Usuario::all();

        return view('usuarios', [
            'usuarios' => $usuarios
        ]);
    }
}