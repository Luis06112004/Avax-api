<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $products = [
            [
                'sku' => 'NK-AM90-BK',
                'name' => 'Nike Air Max 90 Negro',
                'brand' => 'NIKE',
                'category' => 'Lifestyle',
                'description' => 'Diseño icónico Air Max con amortiguación visible y materiales premium.',
                'price' => 459,
                'old_price' => 549,
                'stock' => 24,
                'sizes' => [38, 39, 40, 41, 42],
                'colors' => ['black'],
                'badge' => 'HOT',
                'status' => 'active',
                'images' => [],
            ],
            [
                'sku' => 'NK-AM90-RD',
                'name' => 'Nike Air Max 90 Rojo',
                'brand' => 'NIKE',
                'category' => 'Lifestyle',
                'description' => 'Variante en color rojo del clásico Air Max 90.',
                'price' => 479,
                'old_price' => null,
                'stock' => 12,
                'sizes' => [39, 40, 41, 42, 43],
                'colors' => ['red'],
                'badge' => 'NEW',
                'status' => 'active',
                'images' => [],
            ],
            [
                'sku' => 'NK-AM90-WH',
                'name' => 'Nike Air Max 90 Blanco',
                'brand' => 'NIKE',
                'category' => 'Lifestyle',
                'description' => 'Air Max 90 en colorway blanco total.',
                'price' => 459,
                'old_price' => null,
                'stock' => 0,
                'sizes' => [40, 41, 42],
                'colors' => ['white'],
                'badge' => null,
                'status' => 'out_of_stock',
                'images' => [],
            ],
            [
                'sku' => 'AD-SAMBA-BK',
                'name' => 'Adidas Samba OG',
                'brand' => 'ADIDAS',
                'category' => 'Casual',
                'description' => 'Clásico Samba en negro con detalles en blanco.',
                'price' => 359,
                'old_price' => null,
                'stock' => 38,
                'sizes' => [38, 39, 40, 41, 42, 43],
                'colors' => ['black', 'white'],
                'badge' => 'NEW',
                'status' => 'active',
                'images' => [],
            ],
            [
                'sku' => 'NB-9060-GR',
                'name' => 'New Balance 9060',
                'brand' => 'NEW BALANCE',
                'category' => 'Running',
                'description' => 'Diseño retro futurista con tecnología ABZORB.',
                'price' => 459,
                'old_price' => 519,
                'stock' => 18,
                'sizes' => [40, 41, 42, 43],
                'colors' => ['gray'],
                'badge' => null,
                'status' => 'active',
                'images' => [],
            ],
            [
                'sku' => 'JR-1MID-BL',
                'name' => 'Jordan 1 Mid',
                'brand' => 'JORDAN',
                'category' => 'Basketball',
                'description' => 'Silueta legendaria en colorway azul.',
                'price' => 489,
                'old_price' => null,
                'stock' => 7,
                'sizes' => [40, 41, 42, 43, 44],
                'colors' => ['blue', 'white'],
                'badge' => 'HOT',
                'status' => 'active',
                'images' => [],
            ],
            [
                'sku' => 'PM-SUEDE-BL',
                'name' => 'Puma Suede Classic',
                'brand' => 'PUMA',
                'category' => 'Lifestyle',
                'description' => 'Modelo icónico de los 70 en gamuza azul.',
                'price' => 299,
                'old_price' => null,
                'stock' => 22,
                'sizes' => [39, 40, 41, 42, 43],
                'colors' => ['blue'],
                'badge' => null,
                'status' => 'active',
                'images' => [],
            ],
            [
                'sku' => 'CV-CT70-BK',
                'name' => 'Converse Chuck 70',
                'brand' => 'CONVERSE',
                'category' => 'Casual',
                'description' => 'Versión premium del clásico All Star.',
                'price' => 339,
                'old_price' => null,
                'stock' => 30,
                'sizes' => [38, 39, 40, 41, 42],
                'colors' => ['black'],
                'badge' => null,
                'status' => 'draft',
                'images' => [],
            ],
        ];

        foreach ($products as $data) {
            Product::updateOrCreate(['sku' => $data['sku']], $data);
        }
    }
}
