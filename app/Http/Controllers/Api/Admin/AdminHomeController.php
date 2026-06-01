<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\HomeSeccion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * CRUD de las secciones de la homepage (panel admin).
 *
 *   GET    /api/admin/home/secciones              listar todas (incluye inactivas)
 *   POST   /api/admin/home/secciones              crear
 *   PUT    /api/admin/home/secciones/reordenar    reordenar en bulk
 *   GET    /api/admin/home/secciones/{id}         detalle
 *   PUT    /api/admin/home/secciones/{id}         actualizar
 *   DELETE /api/admin/home/secciones/{id}         eliminar
 */
class AdminHomeController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => HomeSeccion::orderBy('orden')->get(),
        ]);
    }

    public function show(int $id): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => HomeSeccion::findOrFail($id),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'tipo' => 'required|string|in:hero,marcas,popular,promo_banner,nuevos,destacados,testimonios,instagram',
            'titulo' => 'nullable|string|max:255',
            'subtitulo' => 'nullable|string|max:500',
            'configuracion' => 'required|array',
            'orden' => 'integer|min:0',
            'activo' => 'boolean',
        ]);

        $seccion = HomeSeccion::create([
            'tipo' => $request->input('tipo'),
            'titulo' => $request->input('titulo'),
            'subtitulo' => $request->input('subtitulo'),
            'configuracion' => $request->input('configuracion'),
            'orden' => $request->input('orden', (HomeSeccion::max('orden') ?? -1) + 1),
            'activo' => $request->boolean('activo', true),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Sección creada exitosamente.',
            'data' => $seccion,
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $seccion = HomeSeccion::findOrFail($id);

        $request->validate([
            'titulo' => 'nullable|string|max:255',
            'subtitulo' => 'nullable|string|max:500',
            'configuracion' => 'sometimes|array',
            'orden' => 'sometimes|integer|min:0',
            'activo' => 'sometimes|boolean',
        ]);

        $datos = $request->only(['titulo', 'subtitulo', 'configuracion', 'orden']);
        if ($request->has('activo')) {
            $datos['activo'] = $request->boolean('activo');
        }

        $seccion->update($datos);

        return response()->json([
            'success' => true,
            'message' => 'Sección actualizada exitosamente.',
            'data' => $seccion->fresh(),
        ]);
    }

    public function reorder(Request $request): JsonResponse
    {
        $request->validate([
            'orden' => 'required|array',
            'orden.*.id' => 'required|integer|exists:home_secciones,id',
            'orden.*.orden' => 'required|integer|min:0',
        ]);

        foreach ($request->input('orden') as $item) {
            HomeSeccion::where('id', $item['id'])->update(['orden' => $item['orden']]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Orden actualizado.',
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        HomeSeccion::findOrFail($id)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Sección eliminada.',
        ]);
    }
}
