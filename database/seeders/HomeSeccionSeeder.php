<?php

namespace Database\Seeders;

use App\Models\HomeSeccion;
use Illuminate\Database\Seeder;

/**
 * Secciones de la homepage de AVAX — replican EXACTAMENTE el orden y los
 * componentes de la home original, ahora editables/reordenables/activables.
 *
 * Orden original: Hero · Marcas · Popular · Promo · Nuevos · Destacados ·
 *                 Testimonios · Instagram
 */
class HomeSeccionSeeder extends Seeder
{
    public function run(): void
    {
        $secciones = [
            [
                'tipo' => 'hero',
                'titulo' => null,
                'subtitulo' => null,
                'orden' => 0,
                'activo' => true,
                'configuracion' => [
                    'producto_ids' => [], // [] = auto (destacados/nuevos del catálogo)
                    'limite' => 3,
                ],
            ],
            [
                'tipo' => 'marcas',
                'titulo' => 'Las mejores del mercado',
                'subtitulo' => 'Marcas',
                'orden' => 1,
                'activo' => true,
                'configuracion' => [
                    'marca_ids' => [],
                    'limite' => 3,
                ],
            ],
            [
                'tipo' => 'popular',
                'titulo' => 'Lo más popular esta semana',
                'subtitulo' => 'Trending ahora',
                'orden' => 2,
                'activo' => true,
                'configuracion' => [
                    'producto_ids' => [],
                    'limite' => 8,
                ],
            ],
            [
                'tipo' => 'promo_banner',
                'titulo' => null,
                'subtitulo' => null,
                'orden' => 3,
                'activo' => true,
                'configuracion' => [
                    'etiqueta' => 'Oferta limitada',
                    'boton_texto' => 'Ver ofertas',
                    'boton_link' => '/ofertas',
                ],
            ],
            [
                'tipo' => 'nuevos',
                'titulo' => 'Nuevos Lanzamientos',
                'subtitulo' => 'Drops 2026',
                'orden' => 4,
                'activo' => true,
                'configuracion' => [
                    'producto_ids' => [],
                    'limite' => 8,
                ],
            ],
            [
                'tipo' => 'destacados',
                'titulo' => 'Productos Destacados',
                'subtitulo' => 'Selección del equipo',
                'orden' => 5,
                'activo' => true,
                'configuracion' => [
                    'producto_ids' => [],
                    'limite' => 8,
                ],
            ],
            [
                'tipo' => 'testimonios',
                'titulo' => 'Lo que dicen nuestros clientes',
                'subtitulo' => 'Reseñas verificadas de compradores reales',
                'orden' => 6,
                'activo' => true,
                'configuracion' => [],
            ],
            [
                'tipo' => 'instagram',
                'titulo' => 'Síguenos en Instagram',
                'subtitulo' => 'Inspiración diaria · Lookbook · Drops exclusivos',
                'orden' => 7,
                'activo' => true,
                'configuracion' => [],
            ],
        ];

        foreach ($secciones as $s) {
            HomeSeccion::updateOrCreate(['tipo' => $s['tipo']], $s);
        }

        // Limpiar tipos viejos que NO pertenecen a la home original de AVAX
        HomeSeccion::whereIn('tipo', ['beneficios', 'categorias', 'exploracion'])->delete();
    }
}
