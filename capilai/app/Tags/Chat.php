<?php

namespace App\Tags;

use Statamic\Tags\Tags;
use App\Models\ChatMessage;
use Illuminate\Support\Facades\Log;

class Chat extends Tags
{
    public function ultimo()
    {
        try {

            $userId = session('usuario_id');

            if (!$userId) {
                return 'No hay sesión activa.';
            }

            $chat = ChatMessage::where('user_id', $userId)
                ->latest()
                ->first();

            if (!$chat) {
                return 'Aún no has iniciado una conversación.';
            }

            if (!$chat->answer) {
                return 'No hay respuesta disponible.';
            }

            $contenido = $chat->answer;

            if ($contenido === null || $contenido === '') {

                Log::warning('Respuesta vacía en BD', [
                    'user_id' => $userId,
                    'chat_id' => $chat->id
                ]);

                return 'Error: la respuesta está vacía.';
            }

            return nl2br(e($contenido));

        } catch (\Exception $e) {

            Log::error('Error en Statamic Tag Chat@ultimo', [
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ]);

            return 'Error interno al obtener el chat.';
        }
    }
}
