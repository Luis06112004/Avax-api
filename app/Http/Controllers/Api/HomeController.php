<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Categoria;
use App\Models\HomeSeccion;
use App\Models\Marca;
use App\Models\Producto;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

/**
 * Endpoint público que sirve las secciones activas de la homepage,
 * enriquecidas con los datos reales (productos, categorías, marcas).
 *
 *   GET /api/home/secciones
 */
class HomeController extends Controller
{
    public function index(): JsonResponse
    {
        $secciones = HomeSeccion::where('activo', true)
            ->orderBy('orden')
            ->get()
            ->map(fn (HomeSeccion $s) => $this->resolverSeccion($s))
            ->values();

        return response()->json([
            'success' => true,
            'data' => $secciones,
        ]);
    }

    private function resolverSeccion(HomeSeccion $seccion): array
    {
        $cfg = $seccion->configuracion ?? [];

        $base = [
            'id' => $seccion->id,
            'tipo' => $seccion->tipo,
            'titulo' => $seccion->titulo,
            'subtitulo' => $seccion->subtitulo,
            'orden' => $seccion->orden,
        ];

        $datos = match ($seccion->tipo) {
            'hero' => array_merge($cfg, [
                'productos' => $this->resolverProductos($cfg, 'hero'),
            ]),
            'destacados' => ['productos' => $this->resolverProductos($cfg, 'destacados'), 'config' => $cfg],
            'nuevos' => ['productos' => $this->resolverProductos($cfg, 'nuevos'), 'config' => $cfg],
            'popular' => ['productos' => $this->resolverProductos($cfg, 'popular'), 'config' => $cfg],
            'marcas' => ['marcas' => $this->resolverMarcas($cfg), 'config' => $cfg],
            // promo_banner/testimonios/instagram: config directa
            default => $cfg,
        };

        return array_merge($base, ['datos' => $datos]);
    }

    private function resolverProductos(array $cfg, string $tipo): array
    {
        $ids = $cfg['producto_ids'] ?? [];
        $limite = (int) ($cfg['limite'] ?? 8);

        $q = Producto::query()
            ->where('activo', true)
            ->with(['imagenes', 'marca']);

        if (!empty($ids)) {
            $q->whereIn('id', $ids)
              ->orderByRaw('FIELD(id, ' . implode(',', array_map('intval', $ids)) . ')');
        } else {
            if ($tipo === 'destacados') {
                $q->where('destacado', true);
            } elseif ($tipo === 'nuevos') {
                $q->where('nuevo', true);
            } elseif ($tipo === 'popular') {
                $q->where('en_stock', true);
            } else {
                // hero u otros: destacados o nuevos como fallback
                $q->where(fn ($w) => $w->where('destacado', true)->orWhere('nuevo', true));
            }
            $q->orderByDesc('created_at')->take($limite);
        }

        return $q->get()->map(fn (Producto $p) => $this->shapeProducto($p))->values()->all();
    }

    private function resolverCategorias(array $cfg): array
    {
        $ids = $cfg['categoria_ids'] ?? [];
        $limite = (int) ($cfg['limite'] ?? 4);

        $q = Categoria::query()
            ->withCount(['productos' => fn ($w) => $w->where('activo', true)]);

        if (!empty($ids)) {
            $q->whereIn('id', $ids)
              ->orderByRaw('FIELD(id, ' . implode(',', array_map('intval', $ids)) . ')');
        } else {
            $q->orderBy('nombre')->take($limite);
        }

        return $q->get()->map(fn (Categoria $c) => [
            'id' => (string) $c->id,
            'nombre' => $c->nombre,
            'slug' => $c->slug,
            'descripcion' => $c->descripcion,
            'imagen' => $c->imagen,
            'productos_count' => (int) $c->productos_count,
        ])->values()->all();
    }

    private function resolverMarcas(array $cfg): array
    {
        $ids = $cfg['marca_ids'] ?? [];
        $limite = (int) ($cfg['limite'] ?? 12);

        $q = Marca::query()
            ->withCount(['productos' => fn ($w) => $w->where('activo', true)]);

        if (!empty($ids)) {
            $q->whereIn('id', $ids)
              ->orderByRaw('FIELD(id, ' . implode(',', array_map('intval', $ids)) . ')');
        } else {
            $q->orderBy('nombre')->take($limite);
        }

        return $q->get()->map(fn (Marca $m) => [
            'id' => (string) $m->id,
            'nombre' => $m->nombre,
            'slug' => $m->slug,
            'logo' => $m->logo,
            'productos_count' => (int) $m->productos_count,
        ])->values()->all();
    }

    /**
     * Mismo shape que Shop\CatalogController::shape() para que las tarjetas
     * del frontend (ProductCard / HeroCarousel) calcen sin adaptaciones.
     */
    private function shapeProducto(Producto $p): array
    {
        $images = $p->relationLoaded('imagenes')
            ? $p->imagenes->pluck('url')->filter()->values()->all()
            : [];
        if (empty($images) && $p->imagen_principal) {
            $images = [$p->imagen_principal];
        }

        $badge = null;
        if ($p->destacado) $badge = 'HOT';
        elseif ($p->nuevo) $badge = 'NEW';
        elseif ($p->precio_comparacion && (float) $p->precio_comparacion > (float) $p->precio) $badge = 'SALE';

        $discountLabel = null;
        if ($p->precio_comparacion && (float) $p->precio_comparacion > (float) $p->precio) {
            $pct = (int) round((1 - ((float) $p->precio / (float) $p->precio_comparacion)) * 100);
            if ($pct > 0) $discountLabel = "-{$pct}%";
        }

        return [
            'id' => (string) $p->id,
            'slug' => $p->slug,
            'sku' => $p->sku,
            'name' => $p->nombre,
            'brand' => $p->marca_nombre ? mb_strtoupper($p->marca_nombre) : '',
            'category' => $p->categoria_principal ?? '',
            'price' => (float) $p->precio,
            'oldPrice' => $p->precio_comparacion ? (float) $p->precio_comparacion : null,
            'discountLabel' => $discountLabel,
            'image' => $images[0] ?? '',
            'images' => $images,
            'sizes' => [],
            'colors' => $p->color ? [$p->color] : [],
            'badge' => $badge,
            'gender' => $p->genero,
            'stock' => (int) $p->stock_total,
            'rating' => 4.8,
        ];
    }
}
