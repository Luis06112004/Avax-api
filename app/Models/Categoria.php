<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Categoria extends Model
{
    protected $fillable = [
        'ecommerce_id',
        'nombre',
        'slug',
        'descripcion',
        'imagen',
        'padre_ecommerce_id',
        'productos_count',
    ];

    public function productos(): BelongsToMany
    {
        return $this->belongsToMany(Producto::class, 'categoria_producto');
    }
}
