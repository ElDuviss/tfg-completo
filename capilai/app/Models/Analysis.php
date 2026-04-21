<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Analysis extends Model
{
    protected $table = 'analysis';

    protected $fillable = [
        'user_id',
        'type',
        'answers',
        'ai_response',
        'extra_data',
    ];

    protected $casts = [
        'answers' => 'array',
        'extra_data' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(Usuario::class, 'user_id');
    }
}
