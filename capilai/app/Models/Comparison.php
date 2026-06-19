<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Comparison extends Model
{
    protected $table = 'comparisons';

    protected $fillable = [
        'user_id',
        'photo_a_id',
        'photo_b_id',
        'comparison_text',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function photoA()
    {
        return $this->belongsTo(Foto::class, 'photo_a_id');
    }

    public function photoB()
    {
        return $this->belongsTo(Foto::class, 'photo_b_id');
    }
}