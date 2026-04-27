<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EcommerceSyncCambio extends Model
{
    protected $table = 'ecommerce_sync_cambios';

    protected $fillable = [
        'sync_id', 'tipo', 'subtipo',
        'producto_id', 'ecommerce_id', 'sku', 'nombre',
        'antes', 'despues',
    ];

    protected $casts = [
        'antes' => 'array',
        'despues' => 'array',
    ];

    public function syncJob(): BelongsTo
    {
        return $this->belongsTo(EcommerceSyncJob::class, 'sync_id');
    }
}
