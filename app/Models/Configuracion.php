<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Configuracion extends Model
{
    protected $table = 'configuracion';

    protected $fillable = [
        'nombre_tienda', 'descripcion_tienda', 'email_contacto',
        'telefono_contacto', 'direccion', 'moneda', 'simbolo_moneda',
        'logo_url', 'color_primario', 'redes_sociales',
        'envio_gratis_desde', 'costo_envio_base', 'igv_porcentaje',
        'meta_titulo', 'meta_descripcion',
    ];

    protected $casts = [
        'redes_sociales' => 'array',
    ];
}