<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Usuario;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use App\Models\Cuestionario;

class AuthController extends Controller
{
    public function redirectToGoogle()
    {
        return Socialite::driver('google')->redirect();
    }

    public function handleGoogleCallback()
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (\Exception $e) {
            return redirect('/')->with('login_error', 'Error al iniciar sesión con Google.');
        }

        $usuario = Usuario::where('email', $googleUser->getEmail())->first();

        if (! $usuario) {

            $baseUsername = Str::slug($googleUser->getName());
            $username = $baseUsername;
            $contador = 1;

            while (Usuario::where('username', $username)->exists()) {
                $username = $baseUsername . '-' . $contador;
                $contador++;
            }

            $usuario = Usuario::create([
                'email' => $googleUser->getEmail(),
                'username' => $username,
                'password' => Hash::make(Str::random(16)),
            ]);
        }

        session(['usuario_id' => $usuario->id]);

        $cuestionario = Cuestionario::where('user_id', $usuario->id)->first();

        if ($cuestionario) {
            return redirect('/analysis/menu_analysis');
        }

        return redirect('/questionaire');
    }

}