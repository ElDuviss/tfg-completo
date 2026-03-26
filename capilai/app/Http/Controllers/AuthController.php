<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Usuario;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class AuthController extends Controller
{
    // Redirige a Google
    public function redirectToGoogle()
    {
        return Socialite::driver('google')->redirect();
    }

    // Google devuelve los datos aquí
    public function handleGoogleCallback()
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (\Exception $e) {
            return redirect('/')->with('login_error', 'Error al iniciar sesión con Google.');
        }

        // Buscar si ya existe por email
        $usuario = Usuario::where('email', $googleUser->getEmail())->first();

        if (! $usuario) {

            // Generar username único
            $baseUsername = Str::slug($googleUser->getName());
            $username = $baseUsername;
            $contador = 1;

            while (Usuario::where('username', $username)->exists()) {
                $username = $baseUsername . '-' . $contador;
                $contador++;
            }

            // Crear usuario nuevo
            $usuario = Usuario::create([
                'email' => $googleUser->getEmail(),
                'username' => $username,
                'password' => Hash::make(Str::random(16)),
            ]);
        }

        // Guardar sesión
        session(['usuario_id' => $usuario->id]);

        return redirect('/questionaire');
    }

}