<?php

namespace App\Http\Controllers\Api\Shop;

use App\Http\Controllers\Controller;
use App\Models\Categoria;
use App\Models\Marca;
use App\Models\Producto;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Catalogo publico (lectura) para el frontend de la tienda.
 *
 * Endpoints:
 *   GET /api/shop/productos              listado con filtros + paginacion
 *   GET /api/shop/productos/destacados   destacados (badge HOT/NEW)
 *   GET /api/shop/productos/populares    activos con stock, ordenados por nuevos
 *   GET /api/shop/productos/ofertas      con precio_comparacion (descuento)
 *   GET /api/shop/productos/{slug}       detalle por slug
 *   GET /api/shop/marcas                 lista de marcas
 *   GET /api/shop/categorias             lista de categorias
 */
class CatalogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $q = Producto::activos()->with(['imagenes', 'tallas', 'marca']);

        if ($search = $request->query('q')) {
            $q->where(function ($w) use ($search) {
                $w->where('nombre', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%")
                  ->orWhere('marca_nombre', 'like', "%{$search}%");
            });
        }

        if ($brand = $request->query('marca')) {
            $q->where('marca_nombre', $brand);
        }

        if ($category = $request->query('categoria')) {
            $q->where('categoria_principal', $category);
        }

        if ($priceMin = $request->query('precio_min')) {
            $q->where('precio', '>=', (float) $priceMin);
        }

        if ($priceMax = $request->query('precio_max')) {
            $q->where('precio', '<=', (float) $priceMax);
        }

        $sort = $request->query('sort', 'nuevos');
        match ($sort) {
            'precio_asc' => $q->orderBy('precio', 'asc'),
            'precio_desc' => $q->orderBy('precio', 'desc'),
            'nombre' => $q->orderBy('nombre', 'asc'),
            default => $q->orderByDesc('created_at'),
        };

        $perPage = min((int) $request->query('per_page', 24), 100);
        $page = $q->paginate($perPage);

        return response()->json([
            'data' => collect($page->items())->map(fn (Producto $p) => $this->shape($p))->values(),
            'pagination' => [
                'total' => $page->total(),
                'per_page' => $page->perPage(),
                'current_page' => $page->currentPage(),
                'last_page' => $page->lastPage(),
            ],
        ]);
    }

    public function destacados(): JsonResponse
    {
        $list = Producto::activos()
            ->with(['imagenes', 'marca'])
            ->where(function ($w) {
                $w->where('destacado', true)->orWhere('nuevo', true);
            })
            ->orderByDesc('created_at')
            ->take(8)
            ->get();

        return response()->json([
            'data' => $list->map(fn (Producto $p) => $this->shape($p))->values(),
        ]);
    }

    public function populares(): JsonResponse
    {
        $list = Producto::activos()
            ->with(['imagenes', 'marca'])
            ->where('en_stock', true)
            ->orderByDesc('created_at')
            ->take(8)
            ->get();

        return response()->json([
            'data' => $list->map(fn (Producto $p) => $this->shape($p))->values(),
        ]);
    }

    public function ofertas(): JsonResponse
    {
        $list = Producto::activos()
            ->with(['imagenes', 'marca'])
            ->whereNotNull('precio_comparacion')
            ->whereColumn('precio_comparacion', '>', 'precio')
            ->orderByDesc('created_at')
            ->take(24)
            ->get();

        return response()->json([
            'data' => $list->map(fn (Producto $p) => $this->shape($p))->values(),
        ]);
    }

    public function show(string $slug): JsonResponse
    {
        $p = Producto::activos()
            ->with(['imagenes', 'tallas', 'marca', 'categorias'])
            ->where('slug', $slug)
            ->first();

        if (!$p) return response()->json(['message' => 'No encontrado'], 404);

        return response()->json([
            'data' => array_merge($this->shape($p), [
                'description_long' => $p->descripcion,
                'tallas_detalle' => $p->tallas->map(fn ($t) => [
                    'talla' => $t->talla,
                    'precio_final' => (float) $t->precio_final,
                    'stock' => (int) $t->stock,
                ])->all(),
            ]),
        ]);
    }

    public function marcas(): JsonResponse
    {
        $list = Marca::orderBy('nombre')->get();

        return response()->json([
            'data' => $list->map(fn (Marca $m) => [
                'id' => (string) $m->id,
                'nombre' => $m->nombre,
                'slug' => $m->slug,
                'logo' => $m->logo,
                'productos_count' => (int) $m->productos_count,
            ])->values(),
        ]);
    }

    public function categorias(): JsonResponse
    {
        $list = Categoria::orderBy('nombre')->get();

        return response()->json([
            'data' => $list->map(fn (Categoria $c) => [
                'id' => (string) $c->id,
                'nombre' => $c->nombre,
                'slug' => $c->slug,
                'imagen' => $c->imagen,
                'productos_count' => (int) $c->productos_count,
            ])->values(),
        ]);
    }

    /**
     * Shape compatible con el ProductCard del frontend.
     */
    private function shape(Producto $p): array
    {
        $images = $p->relationLoaded('imagenes')
            ? $p->imagenes->pluck('url')->filter()->values()->all()
            : [];
        if (empty($images) && $p->imagen_principal) {
            $images = [$p->imagen_principal];
        }

        $sizes = $p->relationLoaded('tallas')
            ? $p->tallas->map(fn ($t) => $t->talla)->values()->all()
            : [];

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
            'sizes' => $sizes,
            'colors' => $p->color ? [$p->color] : [],
            'badge' => $badge,
            'stock' => (int) $p->stock_total,
            'rating' => 4.8, // placeholder hasta tener reviews reales
        ];
    }
}
