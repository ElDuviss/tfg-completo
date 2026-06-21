<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Datofoto extends Model
{
    protected $table = 'datofotos';

    protected $fillable = [
        'user_id',
        'archivo_json',
        'foto_frontal_id',
        'foto_superior_id',
        'foto_lateral_izquierda_id',
        'foto_lateral_derecha_id'
    ];

    public function fotoFrontal()
    {
        return $this->belongsTo(Foto::class, 'foto_frontal_id');
    }

    public function fotoSuperior()
    {
        return $this->belongsTo(Foto::class, 'foto_superior_id');
    }

    public function fotoLateralIzquierda()
    {
        return $this->belongsTo(Foto::class, 'foto_lateral_izquierda_id');
    }

    public function fotoLateralDerecha()
    {
        return $this->belongsTo(Foto::class, 'foto_lateral_derecha_id');
    }
}
