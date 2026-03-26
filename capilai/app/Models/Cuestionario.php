<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cuestionario extends Model
{
    protected $fillable = [
        'user_id',
        'archivo_json',
    ];
}