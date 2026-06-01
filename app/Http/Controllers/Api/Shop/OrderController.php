<?php

namespace App\Http\Controllers\Api\Shop;

use App\Http\Controllers\Controller;
use App\Models\Pedido;
use App\Models\Producto;
use App\Models\ProductoTalla;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Pedidos de la tienda.
 *
 *   POST /api/shop/pedidos          crea pedido (PÚBLICO — invitado o con sesión)
 *   GET  /api/shop/pedidos          lista pedidos del usuario (auth:sanctum)
 *   GET  /api/shop/pedidos/{numero} detalle por número (auth:sanctum)
 */
class OrderController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        $pedidos = Pedido::with('items')
            ->where('user_id', $userId)
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'data' => $pedidos->map(fn (Pedido $p) => $this->shape($p))->values(),
        ]);
    }

    public function show(Request $request, string $numero): JsonResponse
    {
        $pedido = Pedido::with('items')
            ->where('user_id', $request->user()->id)
            ->where('numero', $numero)
            ->first();

        if (!$pedido) {
            return response()->json(['message' => 'No encontrado'], 404);
        }

        return response()->json(['data' => $this->shape($pedido)]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'contacto.nombres' => ['required', 'string', 'max:120'],
            'contacto.apellidos' => ['required', 'string', 'max:120'],
            'contacto.email' => ['required', 'email', 'max:191'],
            'contacto.telefono' => ['required', 'string', 'max:30'],

            'envio.departamento' => ['required', 'string', 'max:100'],
            'envio.provincia' => ['required', 'string', 'max:100'],
            'envio.distrito' => ['required', 'string', 'max:100'],
            'envio.direccion' => ['required', 'string', 'max:255'],
            'envio.referencia' => ['nullable', 'string', 'max:255'],
            'envio.notas' => ['nullable', 'string', 'max:1000'],

            'envio_metodo.id' => ['required', 'string', 'max:30'],
            'envio_metodo.nombre' => ['required', 'string', 'max:120'],
            'envio_metodo.costo' => ['required', 'numeric', 'min:0'],

            'pago.metodo' => ['required', 'in:tarjeta,yape,transferencia'],
            'pago.referencia' => ['nullable', 'string', 'max:60'],

            'items' => ['required', 'array', 'min:1'],
            'items.*.producto_id' => ['nullable'],
            'items.*.slug' => ['required', 'string', 'max:220'],
            'items.*.nombre' => ['required', 'string', 'max:200'],
            'items.*.marca' => ['nullable', 'string', 'max:150'],
            'items.*.imagen' => ['nullable', 'string', 'max:500'],
            'items.*.talla' => ['nullable', 'string', 'max:50'],
            'items.*.color' => ['nullable', 'string', 'max:80'],
            'items.*.precio_unitario' => ['required', 'numeric', 'min:0'],
            'items.*.cantidad' => ['required', 'integer', 'min:1'],
        ]);

        $subtotal = 0.0;
        foreach ($data['items'] as $it) {
            $subtotal += ((float) $it['precio_unitario']) * ((int) $it['cantidad']);
        }
        $envioCosto = (float) $data['envio_metodo']['costo'];
        $total = $subtotal + $envioCosto;

        // Usuario autenticado si vino token Sanctum; null si compra como invitado.
        $userId = auth('sanctum')->id();

        $pedido = DB::transaction(function () use ($data, $userId, $subtotal, $envioCosto, $total) {
            $numero = $this->generarNumero();

            $pedido = Pedido::create([
                'numero' => $numero,
                'user_id' => $userId,
                'estado' => 'pagado',

                'contacto_nombres' => $data['contacto']['nombres'],
                'contacto_apellidos' => $data['contacto']['apellidos'],
                'contacto_email' => mb_strtolower($data['contacto']['email']),
                'contacto_telefono' => $data['contacto']['telefono'],

                'envio_departamento' => $data['envio']['departamento'],
                'envio_provincia' => $data['envio']['provincia'],
                'envio_distrito' => $data['envio']['distrito'],
                'envio_direccion' => $data['envio']['direccion'],
                'envio_referencia' => $data['envio']['referencia'] ?? null,
                'envio_notas' => $data['envio']['notas'] ?? null,

                'envio_metodo_id' => $data['envio_metodo']['id'],
                'envio_metodo_nombre' => $data['envio_metodo']['nombre'],
                'envio_costo' => $envioCosto,

                'pago_metodo' => $data['pago']['metodo'],
                'pago_referencia' => $data['pago']['referencia'] ?? null,

                'subtotal' => $subtotal,
                'total' => $total,
                'confirmado_at' => now(),
            ]);

            foreach ($data['items'] as $it) {
                // Resolve product (by id or by slug as fallback) and lock the row
                // to prevent race conditions when multiple orders compete for the
                // same stock.
                $producto = null;
                if (!empty($it['producto_id'])) {
                    $producto = Producto::where('id', $it['producto_id'])
                        ->lockForUpdate()
                        ->first();
                }
                if (!$producto && !empty($it['slug'])) {
                    $producto = Producto::where('slug', $it['slug'])
                        ->lockForUpdate()
                        ->first();
                }

                $cantidad = (int) $it['cantidad'];

                // Decrement size-specific stock if a talla was chosen and the
                // producto + talla row exists.
                if ($producto && !empty($it['talla']) && $it['talla'] !== '—') {
                    $talla = ProductoTalla::where('producto_id', $producto->id)
                        ->where('talla', (string) $it['talla'])
                        ->lockForUpdate()
                        ->first();
                    if ($talla) {
                        $talla->stock = max(0, ((int) $talla->stock) - $cantidad);
                        $talla->save();
                    }
                }

                // Decrement global stock_total of the producto.
                if ($producto) {
                    $producto->stock_total = max(0, ((int) $producto->stock_total) - $cantidad);
                    $producto->en_stock = $producto->stock_total > 0;
                    $producto->save();
                }

                $pedido->items()->create([
                    'producto_id' => $producto?->id,
                    'producto_slug' => $it['slug'],
                    'producto_nombre' => $it['nombre'],
                    'marca' => $it['marca'] ?? null,
                    'imagen' => $it['imagen'] ?? null,
                    'talla' => $it['talla'] ?? null,
                    'color' => $it['color'] ?? null,
                    'precio_unitario' => (float) $it['precio_unitario'],
                    'cantidad' => $cantidad,
                    'subtotal' => ((float) $it['precio_unitario']) * $cantidad,
                ]);
            }

            return $pedido->load('items');
        });

        return response()->json([
            'data' => $this->shape($pedido),
        ], 201);
    }

    private function generarNumero(): string
    {
        do {
            $numero = 'AVX-' . date('Y') . '-' . str_pad((string) random_int(10000, 99999), 5, '0', STR_PAD_LEFT);
        } while (Pedido::where('numero', $numero)->exists());
        return $numero;
    }

    private function shape(Pedido $p): array
    {
        return [
            'id' => (string) $p->id,
            'numero' => $p->numero,
            'estado' => $p->estado,
            'contacto' => [
                'nombres' => $p->contacto_nombres,
                'apellidos' => $p->contacto_apellidos,
                'email' => $p->contacto_email,
                'telefono' => $p->contacto_telefono,
            ],
            'envio' => [
                'departamento' => $p->envio_departamento,
                'provincia' => $p->envio_provincia,
                'distrito' => $p->envio_distrito,
                'direccion' => $p->envio_direccion,
                'referencia' => $p->envio_referencia,
                'notas' => $p->envio_notas,
            ],
            'envio_metodo' => [
                'id' => $p->envio_metodo_id,
                'nombre' => $p->envio_metodo_nombre,
                'costo' => (float) $p->envio_costo,
            ],
            'pago' => [
                'metodo' => $p->pago_metodo,
                'referencia' => $p->pago_referencia,
            ],
            'subtotal' => (float) $p->subtotal,
            'total' => (float) $p->total,
            'confirmado_at' => $p->confirmado_at?->toIso8601String(),
            'created_at' => $p->created_at?->toIso8601String(),
            'items' => $p->items->map(fn ($it) => [
                'id' => (string) $it->id,
                'producto_id' => $it->producto_id ? (string) $it->producto_id : null,
                'slug' => $it->producto_slug,
                'nombre' => $it->producto_nombre,
                'marca' => $it->marca,
                'imagen' => $it->imagen,
                'talla' => $it->talla,
                'color' => $it->color,
                'precio_unitario' => (float) $it->precio_unitario,
                'cantidad' => (int) $it->cantidad,
                'subtotal' => (float) $it->subtotal,
            ])->all(),
        ];
    }
}
