<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cupon extends Model
{
    protected $table = 'cupones';

    protected $fillable = [
        'codigo', 'tipo', 'valor', 'minimo_compra',
        'maximo_descuento', 'usos_maximos', 'usos_actuales',
        'activo', 'fecha_inicio', 'fecha_fin',
    ];

    protected $casts = [
        'activo'       => 'boolean',
        'fecha_inicio' => 'date',
        'fecha_fin'    => 'date',
        'valor'        => 'float',
    ];
}