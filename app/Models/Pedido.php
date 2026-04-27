<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pedido extends Model
{
    protected $table = 'pedidos';

    protected $fillable = [
        'numero',
        'user_id',
        'estado',
        'contacto_nombres',
        'contacto_apellidos',
        'contacto_email',
        'contacto_telefono',
        'envio_departamento',
        'envio_provincia',
        'envio_distrito',
        'envio_direccion',
        'envio_referencia',
        'envio_notas',
        'envio_metodo_id',
        'envio_metodo_nombre',
        'envio_costo',
        'pago_metodo',
        'pago_referencia',
        'subtotal',
        'total',
        'confirmado_at',
    ];

    protected $casts = [
        'envio_costo' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'total' => 'decimal:2',
        'confirmado_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PedidoItem::class);
    }
}
