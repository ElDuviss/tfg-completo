<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Datofoto extends Model
{
    protected $table = 'datofotos';

    protected $fillable = [
        'user_id',
        'archivo_json'
    ];
}