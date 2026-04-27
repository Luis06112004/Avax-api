<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Producto;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
     * Endpoint de creacion deshabilitado: los productos provienen del e-commerce.
     * Se deja stub que devuelve 422 indicando que hay que crear desde el origen.
     */
    public function store(Request $request): JsonResponse
    {
        return response()->json([
            'message' => 'Los productos se crean en el e-commerce origen y se sincronizan con POST /api/admin/sync/run.',
        ], 422);
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
