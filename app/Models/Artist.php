<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Artist extends Model
{
    //
         //
        protected $fillable = [
        'user_id',
        'alias',
        'fecha_nacimiento',
        'area_id',
    ];

    public function area()
    {
        return $this->belongsTo(Area::class);
    }
}
