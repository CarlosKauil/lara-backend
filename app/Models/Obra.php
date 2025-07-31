<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Obra extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'artist_id', 'area_id', 'nombre', 'archivo', 'genero_tecnica',
        'anio_creacion', 'descripcion', 'estatus_id'
    ];

    public function artist() {
        return $this->belongsTo(Artist::class);
    }

    public function area() {
        return $this->belongsTo(Area::class);
    }

    public function estatus() {
        return $this->belongsTo(EstatusObra::class, 'estatus_id');
    }

    public function mensajesRechazo() {
        return $this->hasMany(MensajeRechazo::class);
    }

        public function user()
    {
        return $this->belongsTo(User::class);
    }
}