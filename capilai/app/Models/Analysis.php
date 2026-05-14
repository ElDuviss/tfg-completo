<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Analysis extends Model
{
    protected $table = 'analysis';

    protected $fillable = [
        'user_id',
        'type',
        'cuestionario_json',
        'fotos_json',
        'ai_response',
    ];

    protected $casts = [
        'cuestionario_json' => 'array',
        'fotos_json'        => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(Usuario::class, 'user_id');
    }
}