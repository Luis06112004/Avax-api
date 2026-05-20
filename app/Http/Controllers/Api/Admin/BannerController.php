<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BannerController extends Controller
{
    // GET /api/admin/banners
    public function index()
    {
        $banners = Banner::orderBy('orden')->get();
        return response()->json(['banners' => $banners]);
    }

    // POST /api/admin/banners
    public function store(Request $request)
    {
        $request->validate([
            'titulo'   => 'required|string|max:255',
            'imagen'   => 'required|image|max:5120', // máximo 5MB
            'orden'    => 'integer|min:1',
        ]);

        $path = $request->file('imagen')->store('banners', 'public');

        $banner = Banner::create([
            'titulo'      => $request->titulo,
            'subtitulo'   => $request->subtitulo,
            'imagen_url'  => Storage::url($path),
            'enlace'      => $request->enlace,
            'activo'      => filter_var($request->activo, FILTER_VALIDATE_BOOLEAN),
            'orden'       => $request->orden ?? (Banner::max('orden') + 1),
            'fecha_inicio' => $request->fecha_inicio ?: null,
            'fecha_fin'    => $request->fecha_fin ?: null,
        ]);

        return response()->json($banner, 201);
    }

    // PUT /api/admin/banners/{id}
    public function update(Request $request, $id)
    {
        $banner = Banner::findOrFail($id);

        $request->validate([
            'titulo' => 'sometimes|required|string|max:255',
            'imagen' => 'sometimes|image|max:5120',
        ]);

        if ($request->hasFile('imagen')) {
            // Borra la imagen anterior
            $oldPath = str_replace('/storage/', '', $banner->imagen_url);
            Storage::disk('public')->delete($oldPath);

            $path = $request->file('imagen')->store('banners', 'public');
            $banner->imagen_url = Storage::url($path);
        }

        $banner->titulo      = $request->titulo      ?? $banner->titulo;
        $banner->subtitulo   = $request->subtitulo   ?? $banner->subtitulo;
        $banner->enlace      = $request->enlace      ?? $banner->enlace;
        $banner->orden       = $request->orden       ?? $banner->orden;
        $banner->fecha_inicio = $request->fecha_inicio ?: $banner->fecha_inicio;
        $banner->fecha_fin   = $request->fecha_fin   ?: $banner->fecha_fin;

        if ($request->has('activo')) {
            $banner->activo = filter_var($request->activo, FILTER_VALIDATE_BOOLEAN);
        }

        $banner->save();

        return response()->json($banner);
    }

    // DELETE /api/admin/banners/{id}
    public function destroy($id)
    {
        $banner = Banner::findOrFail($id);

        // Borra imagen del storage
        $path = str_replace('/storage/', '', $banner->imagen_url);
        Storage::disk('public')->delete($path);

        $banner->delete();

        return response()->json(['message' => 'Banner eliminado']);
    }
}
