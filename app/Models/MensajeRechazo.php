<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MensajeRechazo extends Model
{
    use HasFactory;

    protected $table = 'mensajes_rechazo';

    protected $fillable = ['obra_id', 'admin_id', 'mensaje'];

    public function obra() {
        return $this->belongsTo(Obra::class);
    }

    public function admin() {
        return $this->belongsTo(User::class, 'admin_id');
    }
}