<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Producto;
use App\Models\Pedido;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class StatsController extends Controller
{
    public function index()
    {
        $hoy = Carbon::today();

        // Ventas de hoy (suma de pedidos entregados o procesando)
        $ventas_hoy = Pedido::whereDate('created_at', $hoy)
            ->whereIn('estado', ['entregado', 'procesando', 'enviado'])
            ->sum('total');

        // Total de pedidos
        $pedidos_total = Pedido::count();

        // Total de productos activos
        $productos_total = Producto::where('activo', true)->count();

        // Total de clientes (usuarios con rol cliente)
        $clientes_total = User::where('role', 'cliente')->count();

        // Últimos 5 pedidos
        $pedidos_recientes = Pedido::with('user')
            ->latest()
            ->take(5)
            ->get()
            ->map(fn($p) => [
                'id'              => $p->id,
                'cliente_nombre'  => $p->user?->name ?? trim(($p->contacto_nombres ?? '') . ' ' . ($p->contacto_apellidos ?? '')) ?: 'Cliente',
                'cliente_email'   => $p->user?->email ?? $p->contacto_email ?? '',
                'total'           => (float) $p->total,
                'estado'          => $p->estado,
                'fecha'           => $p->created_at,
            ]);

        // Top 5 productos más vendidos (el nombre ya está desnormalizado en pedido_items)
        $productos_top = DB::table('pedido_items')
            ->select('producto_nombre as nombre', DB::raw('SUM(cantidad) as vendidos'))
            ->groupBy('producto_nombre')
            ->orderByDesc('vendidos')
            ->take(5)
            ->get()
            ->map(fn($p) => [
                'nombre'   => $p->nombre,
                'vendidos' => (int) $p->vendidos,
            ]);

        return response()->json([
            'ventas_hoy'       => (float) $ventas_hoy,
            'pedidos_total'    => $pedidos_total,
            'productos_total'  => $productos_total,
            'clientes_total'   => $clientes_total,
            'pedidos_recientes' => $pedidos_recientes,
            'productos_top'    => $productos_top,
        ]);
    }
}
