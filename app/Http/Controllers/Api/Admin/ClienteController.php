<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Order;
use Illuminate\Http\Request;

class ClienteController extends Controller
{
    // GET /api/admin/clientes?search=xxx&page=1
    public function index(Request $request)
    {
        $query = User::where('role', 'cliente')
            ->withCount('orders')
            ->withSum('orders', 'total');

        if ($request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%$s%")
                  ->orWhere('email', 'like', "%$s%");
            });
        }

        $clientes = $query->latest()->paginate(20);

        $data = $clientes->map(fn($u) => [
            'id'             => $u->id,
            'nombre'         => explode(' ', $u->name)[0] ?? $u->name,
            'apellido'       => implode(' ', array_slice(explode(' ', $u->name), 1)) ?: '',
            'email'          => $u->email,
            'telefono'       => $u->telefono ?? null,
            'direccion'      => $u->direccion ?? null,
            'fecha_registro' => $u->created_at,
            'total_pedidos'  => $u->orders_count,
            'total_gastado'  => (float) ($u->orders_sum_total ?? 0),
            'activo'         => (bool) $u->activo,
        ]);

        return response()->json([
            'clientes'    => $data,
            'total'       => $clientes->total(),
            'total_pages' => $clientes->lastPage(),
        ]);
    }

    // GET /api/admin/clientes/{id}/pedidos
    public function pedidos($id)
    {
        $user = User::findOrFail($id);

        $pedidos = Order::where('user_id', $id)
            ->latest()
            ->get()
            ->map(fn($p) => [
                'id'             => $p->id,
                'cliente_nombre' => $user->name,
                'cliente_email'  => $user->email,
                'total'          => (float) $p->total,
                'estado'         => $p->estado,
                'fecha'          => $p->created_at,
                'items'          => [],
            ]);

        return response()->json(['pedidos' => $pedidos]);
    }

    // PATCH /api/admin/clientes/{id}/estado
    public function toggleEstado(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $user->activo = $request->boolean('activo');
        $user->save();

        return response()->json(['activo' => $user->activo]);
    }
}
