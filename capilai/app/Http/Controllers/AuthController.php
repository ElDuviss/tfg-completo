<?php

namespace App\Http\Controllers;

use App\Models\Usuario;
use App\Models\Cuestionario;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

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

            if (!$googleUser->getEmail()) {

                Log::warning('Google OAuth sin email');

                return redirect('/')
                    ->with(
                        'login_error',
                        'No se pudo obtener el correo electrónico de Google.'
                    );
            }

            $usuario = Usuario::where(
                'email',
                $googleUser->getEmail()
            )->first();

            if (!$usuario) {

                $baseUsername = Str::slug($googleUser->getName());

                if (empty($baseUsername)) {
                    $baseUsername = 'usuario';
                }

                $username = $baseUsername;
                $contador = 1;

                while (
                    Usuario::where('username', $username)->exists()
                ) {
                    $username = $baseUsername . '-' . $contador;
                    $contador++;
                }

                $usuario = Usuario::create([
                    'email'    => $googleUser->getEmail(),
                    'username' => $username,
                    'password' => Hash::make(Str::random(16)),
                ]);

                if (!$usuario) {

                    Log::error('Error creando usuario OAuth', [
                        'email' => $googleUser->getEmail(),
                    ]);

                    return redirect('/')
                        ->with(
                            'login_error',
                            'No se pudo crear la cuenta.'
                        );
                }
            }

            session([
                'usuario_id' => $usuario->id
            ]);

            $tieneCuestionario = Cuestionario::where(
                'user_id',
                $usuario->id
            )->exists();

            if ($tieneCuestionario) {
                return redirect('/analysis/menu_analysis');
            }

            return redirect('/questionaire');

        } catch (\Exception $e) {

            Log::error('Error OAuth Google', [
                'message' => $e->getMessage(),
                'line'    => $e->getLine(),
                'file'    => $e->getFile(),
            ]);

            return redirect('/')
                ->with(
                    'login_error',
                    'Error al iniciar sesión con Google.'
                );
        }
    }
}