<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Comparison extends Model
{
    protected $table = 'comparisons';

    protected $fillable = [
        'user_id',
        'datofoto_nuevo_id',
        'datofoto_antiguo_id',
        'comparison_text',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function datofotoNuevo()
    {
        return $this->belongsTo(Datofoto::class, 'datofoto_nuevo_id');
    }

    public function datofotoAntiguo()
    {
        return $this->belongsTo(Datofoto::class, 'datofoto_antiguo_id');
    }
}
