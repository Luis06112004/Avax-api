<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HomeSeccion extends Model
{
    protected $table = 'home_secciones';

    protected $fillable = [
        'tipo',
        'titulo',
        'subtitulo',
        'configuracion',
        'orden',
        'activo',
    ];

    protected $casts = [
        'configuracion' => 'array',
        'activo' => 'boolean',
    ];
}
