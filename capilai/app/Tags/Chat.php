<?php

namespace App\Tags;

use Statamic\Tags\Tags;
use App\Models\ChatMessage;
use Illuminate\Support\Facades\Storage;

class Chat extends Tags
{
    public function ultimo()
    {
        $userId = session('usuario_id');

        $chat = ChatMessage::where('user_id', $userId)->first();

        if (!$chat) {
            return 'Aún no has iniciado una conversación.';
        }

        if (Storage::exists($chat->answer)) {
            $contenido = Storage::get($chat->answer);
            return nl2br(e($contenido));
        }

        return 'Archivo de respuesta no encontrado.';
    }
}