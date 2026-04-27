<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'sku',
        'name',
        'brand',
        'category',
        'description',
        'price',
        'old_price',
        'stock',
        'sizes',
        'colors',
        'badge',
        'status',
        'images',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'old_price' => 'decimal:2',
        'stock' => 'integer',
        'sizes' => 'array',
        'colors' => 'array',
        'images' => 'array',
    ];

    /**
     * Convierte el modelo al contrato esperado por el frontend (AdminProduct).
     * - id como string
     * - oldPrice (camelCase, nullable)
     * - createdAt / updatedAt en ISO sin timezone (igual al mock)
     */
    public function toArray(): array
    {
        return [
            'id' => (string) $this->id,
            'sku' => $this->sku,
            'name' => $this->name,
            'brand' => $this->brand,
            'category' => $this->category,
            'description' => (string) $this->description,
            'price' => (float) $this->price,
            'oldPrice' => $this->old_price !== null ? (float) $this->old_price : null,
            'stock' => (int) $this->stock,
            'sizes' => $this->sizes ?? [],
            'colors' => $this->colors ?? [],
            'badge' => $this->badge,
            'status' => $this->status,
            'images' => $this->images ?? [],
            'createdAt' => optional($this->created_at)->toIso8601String(),
            'updatedAt' => optional($this->updated_at)->toIso8601String(),
        ];
    }
}
