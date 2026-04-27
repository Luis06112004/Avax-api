<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Producto extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'ecommerce_id', 'nombre', 'slug', 'sku',
        'descripcion_corta', 'descripcion',
        'precio', 'precio_comparacion',
        'marca_id', 'marca_nombre',
        'categoria_principal', 'categoria_slug',
        'color', 'color_hex', 'genero',
        'video_url', 'imagen_principal',
        'en_stock', 'stock_total',
        'activo', 'nuevo', 'destacado',
        'ultima_sync_at', 'ecommerce_raw',
    ];

    protected $casts = [
        'precio' => 'decimal:2',
        'precio_comparacion' => 'decimal:2',
        'en_stock' => 'boolean',
        'activo' => 'boolean',
        'nuevo' => 'boolean',
        'destacado' => 'boolean',
        'ultima_sync_at' => 'datetime',
        'ecommerce_raw' => 'array',
    ];

    public function marca(): BelongsTo
    {
        return $this->belongsTo(Marca::class);
    }

    public function tallas(): HasMany
    {
        return $this->hasMany(ProductoTalla::class);
    }

    public function imagenes(): HasMany
    {
        return $this->hasMany(ProductoImagen::class)->orderBy('orden');
    }

    public function categorias(): BelongsToMany
    {
        return $this->belongsToMany(Categoria::class, 'categoria_producto');
    }

    public function scopeActivos($q) { return $q->where('activo', true); }
    public function scopeInactivos($q) { return $q->where('activo', false); }
}
