<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EcommerceSyncJob extends Model
{
    protected $fillable = [
        'codigo', 'estado',
        'iniciado_at', 'terminado_at', 'duracion_segundos',
        'progreso_pct', 'fase_actual',
        'total_productos', 'procesados',
        'nuevos', 'actualizados', 'removidos', 'sin_cambios',
        'total_requests_http', 'error_mensaje', 'iniciado_por',
    ];

    protected $casts = [
        'iniciado_at' => 'datetime',
        'terminado_at' => 'datetime',
    ];

    public function cambios(): HasMany
    {
        return $this->hasMany(EcommerceSyncCambio::class, 'sync_id');
    }
}
