<?php

namespace App\Tags;

use Statamic\Tags\Tags;
use App\Models\Analysis;

class HairAnalysis extends Tags
{
    public function index()
    {
        $slug = $this->context->get('slug');
        $userId = session('usuario_id');

        $analysis = Analysis::where('user_id', $userId)
            ->where('type', $slug)
            ->first();

        return $analysis ? $analysis->ai_response : 'No hay análisis disponible.';
    }
}