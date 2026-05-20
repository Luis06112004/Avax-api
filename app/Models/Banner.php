<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Banner extends Model
{
    protected $fillable = [
        'titulo', 'subtitulo', 'imagen_url', 'enlace',
        'activo', 'orden', 'fecha_inicio', 'fecha_fin',
    ];

    protected $casts = [
        'activo'       => 'boolean',
        'fecha_inicio' => 'date',
        'fecha_fin'    => 'date',
    ];
}