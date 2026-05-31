<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cupon;
use Illuminate\Http\Request;

class CuponController extends Controller
{
    // GET /api/admin/cupones
    public function index()
    {
        $cupones = Cupon::latest()->get();
        return response()->json(['cupones' => $cupones]);
    }

    // POST /api/admin/cupones
    public function store(Request $request)
    {
        $request->validate([
            'codigo' => 'required|string|max:20|unique:cupones,codigo',
            'tipo'   => 'required|in:porcentaje,monto_fijo',
            'valor'  => 'required|numeric|min:0',
        ]);

        $cupon = Cupon::create([
            'codigo'           => strtoupper($request->codigo),
            'tipo'             => $request->tipo,
            'valor'            => $request->valor,
            'minimo_compra'    => $request->minimo_compra ?: null,
            'maximo_descuento' => $request->maximo_descuento ?: null,
            'usos_maximos'     => $request->usos_maximos ?: null,
            'usos_actuales'    => 0,
            'activo'           => $request->boolean('activo', true),
            'fecha_inicio'     => $request->fecha_inicio ?: null,
            'fecha_fin'        => $request->fecha_fin ?: null,
        ]);

        return response()->json($cupon, 201);
    }

    // PUT /api/admin/cupones/{id}
    public function update(Request $request, $id)
    {
        $cupon = Cupon::findOrFail($id);

        $request->validate([
            'codigo' => "sometimes|required|string|max:20|unique:cupones,codigo,$id",
            'tipo'   => 'sometimes|required|in:porcentaje,monto_fijo',
            'valor'  => 'sometimes|required|numeric|min:0',
        ]);

        $cupon->update([
            'codigo'           => strtoupper($request->codigo ?? $cupon->codigo),
            'tipo'             => $request->tipo             ?? $cupon->tipo,
            'valor'            => $request->valor            ?? $cupon->valor,
            'minimo_compra'    => $request->minimo_compra    ?: $cupon->minimo_compra,
            'maximo_descuento' => $request->maximo_descuento ?: $cupon->maximo_descuento,
            'usos_maximos'     => $request->usos_maximos     ?: $cupon->usos_maximos,
            'activo'           => $request->has('activo') ? $request->boolean('activo') : $cupon->activo,
            'fecha_inicio'     => $request->fecha_inicio     ?: $cupon->fecha_inicio,
            'fecha_fin'        => $request->fecha_fin        ?: $cupon->fecha_fin,
        ]);

        return response()->json($cupon->fresh());
    }

    // PATCH /api/admin/cupones/{id}/estado
    public function toggleEstado(Request $request, $id)
    {
        $cupon = Cupon::findOrFail($id);
        $cupon->activo = $request->boolean('activo');
        $cupon->save();
        return response()->json(['activo' => $cupon->activo]);
    }

    // DELETE /api/admin/cupones/{id}
    public function destroy($id)
    {
        Cupon::findOrFail($id)->delete();
        return response()->json(['message' => 'Cupón eliminado']);
    }
}
