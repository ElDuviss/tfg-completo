<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Analysis extends Model
{
    protected $table = 'analysis';

    protected $fillable = [
        'user_id',
        'type',
        'cuestionario_id',
        'datofoto_id',
        'ai_response',
    ];
    
    public function user()
    {
        return $this->belongsTo(Usuario::class, 'user_id');
    }

    public function cuestionario()
    {
        return $this->belongsTo(Cuestionario::class, 'cuestionario_id');
    }

    public function datofoto()
    {
        return $this->belongsTo(Datofoto::class, 'datofoto_id');
    }
}