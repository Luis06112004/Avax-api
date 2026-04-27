<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Marca extends Model
{
    protected $fillable = [
        'ecommerce_id',
        'nombre',
        'slug',
        'logo',
        'productos_count',
    ];

    public function productos(): HasMany
    {
        return $this->hasMany(Producto::class);
    }
}
