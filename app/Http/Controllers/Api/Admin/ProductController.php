<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Marca;
use App\Models\Producto;
use App\Models\ProductoImagen;
use App\Models\ProductoTalla;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    /**
     * GET /api/admin/productos
     * Devuelve los productos sincronizados desde el e-commerce externo
     * (tabla `productos` en espanol) mapeados al shape AdminProduct
     * que espera el frontend del CMS.
     */
    public function index(): JsonResponse
    {
        $productos = Producto::with(['imagenes', 'tallas'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($productos->map(fn(Producto $p) => $this->toAdminShape($p))->values());
    }

    public function show(Producto $product): JsonResponse
    {
        $product->load(['imagenes', 'tallas']);
        return response()->json($this->toAdminShape($product));
    }

    /**
     * Permite editar campos editables localmente (estado, badge, descripcion).
     * Los campos sincronizados (precio, stock, imagenes) se sobreescriben en el siguiente sync.
     */
    public function update(Request $request, Producto $product): JsonResponse
    {
        $data = $request->validate([
            'name' => ['nullable', 'string', 'max:200'],
            'description' => ['nullable', 'string'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'oldPrice' => ['nullable', 'numeric', 'min:0'],
            'stock' => ['nullable', 'integer', 'min:0'],
            'badge' => ['nullable', 'string'],
            'status' => ['nullable', 'string'],
        ]);

        $update = [];
        if (array_key_exists('name', $data)) $update['nombre'] = $data['name'];
        if (array_key_exists('description', $data)) $update['descripcion'] = $data['description'];
        if (array_key_exists('price', $data)) $update['precio'] = $data['price'];
        if (array_key_exists('oldPrice', $data)) $update['precio_comparacion'] = $data['oldPrice'];
        if (array_key_exists('stock', $data)) $update['stock_total'] = $data['stock'];
        if (array_key_exists('status', $data)) {
            $update['activo'] = $data['status'] === 'active';
        }

        if (!empty($update)) {
            $product->update($update);
        }

        return response()->json($this->toAdminShape($product->fresh(['imagenes', 'tallas'])));
    }

    public function destroy(Producto $product): JsonResponse
    {
        $product->delete();

        return response()->json(null, 204);
    }

    /**
     * POST /api/admin/productos
     * Crea un producto manualmente desde el CMS.
     * Acepta el shape del frontend (campos en inglés tipo AdminProduct).
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:200'],
            'sku' => ['required', 'string', 'max:100', 'unique:productos,sku'],
            'brand' => ['nullable', 'string', 'max:150'],
            'category' => ['nullable', 'string', 'max:150'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'oldPrice' => ['nullable', 'numeric', 'min:0'],
            'stock' => ['required', 'integer', 'min:0'],
            'sizes' => ['nullable', 'array'],
            'sizes.*' => ['nullable'],
            'colors' => ['nullable', 'array'],
            'colors.*' => ['nullable', 'string', 'max:80'],
            'badge' => ['nullable', 'in:HOT,NEW,SALE'],
            'status' => ['nullable', 'in:active,draft,out_of_stock'],
            'images' => ['nullable', 'array'],
            // Aceptamos URLs largas (incl. data URLs base64). El campo en BD
            // es varchar(500), así que los data URLs los recortaremos al
            // persistir si fuera necesario.
            'images.*' => ['nullable', 'string'],
        ]);

        $producto = DB::transaction(function () use ($data) {
            // Slug único basado en el nombre
            $baseSlug = Str::slug($data['name']);
            if ($baseSlug === '') $baseSlug = Str::slug($data['sku']);
            $slug = $baseSlug;
            $i = 2;
            while (Producto::where('slug', $slug)->exists()) {
                $slug = $baseSlug . '-' . $i++;
            }

            // Resolver / crear marca
            $marca = null;
            if (!empty($data['brand'])) {
                $brandName = trim($data['brand']);
                $marca = Marca::where('nombre', $brandName)
                    ->orWhere('slug', Str::slug($brandName))
                    ->first();
                if (!$marca) {
                    $marca = Marca::create([
                        'ecommerce_id' => $this->nextEcommerceId('marcas'),
                        'nombre' => $brandName,
                        'slug' => Str::slug($brandName),
                        'logo' => null,
                        'productos_count' => 0,
                    ]);
                }
            }

            // ecommerce_id sintético (campo unique no nulo en la tabla)
            $ecommerceId = $this->nextEcommerceId('productos');

            $color = null;
            if (!empty($data['colors']) && is_array($data['colors'])) {
                $color = (string) $data['colors'][0];
            }

            $status = $data['status'] ?? 'active';

            $producto = Producto::create([
                'ecommerce_id' => $ecommerceId,
                'nombre' => $data['name'],
                'slug' => $slug,
                'sku' => $data['sku'],
                'descripcion_corta' => null,
                'descripcion' => $data['description'] ?? null,
                'precio' => (float) $data['price'],
                'precio_comparacion' => isset($data['oldPrice']) ? (float) $data['oldPrice'] : null,
                'marca_id' => $marca?->id,
                'marca_nombre' => $marca?->nombre,
                'categoria_principal' => $data['category'] ?? null,
                'categoria_slug' => isset($data['category']) ? Str::slug($data['category']) : null,
                'color' => $color,
                'imagen_principal' => $data['images'][0] ?? null,
                'en_stock' => ((int) $data['stock']) > 0,
                'stock_total' => (int) $data['stock'],
                'activo' => $status !== 'draft',
                'nuevo' => ($data['badge'] ?? null) === 'NEW',
                'destacado' => ($data['badge'] ?? null) === 'HOT',
            ]);

            // Tallas: distribuye el stock total proporcionalmente entre las tallas seleccionadas.
            $sizes = $data['sizes'] ?? [];
            if (is_array($sizes) && count($sizes) > 0) {
                $stockTotal = (int) $data['stock'];
                $count = count($sizes);
                $base = intdiv($stockTotal, $count);
                $resto = $stockTotal - ($base * $count);
                foreach ($sizes as $idx => $talla) {
                    ProductoTalla::create([
                        'producto_id' => $producto->id,
                        'talla' => (string) $talla,
                        'ajuste_precio' => 0,
                        'precio_final' => (float) $data['price'],
                        'stock' => $base + ($idx < $resto ? 1 : 0),
                        'es_predeterminada' => $idx === 0,
                    ]);
                }
            }

            // Imágenes — descartamos blob: URLs (sólo viven en el navegador
            // del usuario que las cargó y no son accesibles desde el server).
            // Para subir archivos reales hace falta un endpoint de upload con
            // storage; aquí aceptamos URLs http(s) o data URLs base64.
            if (!empty($data['images']) && is_array($data['images'])) {
                $orden = 0;
                foreach ($data['images'] as $url) {
                    if (!is_string($url) || $url === '') continue;
                    if (str_starts_with($url, 'blob:')) continue;
                    ProductoImagen::create([
                        'producto_id' => $producto->id,
                        'url' => $url,
                        'alt' => $data['name'],
                        'es_principal' => $orden === 0,
                        'orden' => $orden,
                    ]);
                    $orden++;
                }
                // Si se descartaron todas, también limpiamos imagen_principal.
                if ($orden === 0) {
                    $producto->imagen_principal = null;
                    $producto->save();
                } elseif (str_starts_with((string) $producto->imagen_principal, 'blob:')) {
                    $first = ProductoImagen::where('producto_id', $producto->id)
                        ->orderBy('orden')->value('url');
                    $producto->imagen_principal = $first;
                    $producto->save();
                }
            }

            // Actualiza contador de la marca
            if ($marca) {
                $marca->productos_count = (int) Producto::where('marca_id', $marca->id)->count();
                $marca->save();
            }

            return $producto;
        });

        return response()->json(
            $this->toAdminShape($producto->fresh(['imagenes', 'tallas'])),
            201,
        );
    }

    /**
     * Devuelve el siguiente ecommerce_id disponible para no chocar con el unique.
     * Para productos creados manualmente usamos un offset alto (>= 1_000_000_000)
     * para que no choque con los ids reales del e-commerce externo.
     */
    private function nextEcommerceId(string $tabla): int
    {
        $max = (int) DB::table($tabla)->max('ecommerce_id');
        $base = max($max, 1_000_000_000);
        return $base + 1;
    }

    /**
     * Mapea un Producto (BD sincronizada) al shape AdminProduct del frontend.
     */
    private function toAdminShape(Producto $p): array
    {
        $images = $p->imagenes->pluck('url')->filter()->values()->all();
        if (empty($images) && $p->imagen_principal) {
            $images = [$p->imagen_principal];
        }

        $sizes = $p->tallas
            ->map(fn($t) => is_numeric($t->talla) ? (int) $t->talla : $t->talla)
            ->values()
            ->all();

        $colors = $p->color ? [$p->color] : [];

        $status = (bool) $p->activo
            ? ((int) $p->stock_total > 0 ? 'active' : 'out_of_stock')
            : 'draft';

        $badge = null;
        if ($p->nuevo) $badge = 'NEW';
        if ($p->destacado) $badge = 'HOT';

        return [
            'id' => (string) $p->id,
            'sku' => (string) $p->sku,
            'name' => (string) $p->nombre,
            'brand' => $p->marca_nombre ? mb_strtoupper($p->marca_nombre) : '',
            'category' => $p->categoria_principal ?? '',
            'description' => (string) ($p->descripcion ?? $p->descripcion_corta ?? ''),
            'price' => (float) $p->precio,
            'oldPrice' => $p->precio_comparacion !== null ? (float) $p->precio_comparacion : null,
            'stock' => (int) $p->stock_total,
            'sizes' => $sizes,
            'colors' => $colors,
            'badge' => $badge,
            'status' => $status,
            'images' => $images,
            'createdAt' => optional($p->created_at)->toIso8601String(),
            'updatedAt' => optional($p->updated_at)->toIso8601String(),
        ];
    }
}
