<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductoTalla extends Model
{
    protected $fillable = [
        'producto_id', 'talla', 'ajuste_precio',
        'precio_final', 'stock', 'es_predeterminada',
    ];

    protected $casts = [
        'ajuste_precio' => 'decimal:2',
        'precio_final' => 'decimal:2',
        'es_predeterminada' => 'boolean',
    ];

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }
}
