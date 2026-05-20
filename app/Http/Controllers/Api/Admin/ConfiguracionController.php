<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Configuracion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ConfiguracionController extends Controller
{
    // GET /api/admin/configuracion
    public function index()
    {
        $config = Configuracion::first();

        if (!$config) {
            // Crea la fila inicial si no existe
            $config = Configuracion::create([
                'nombre_tienda'    => 'Avax Store',
                'email_contacto'   => '',
                'moneda'           => 'PEN',
                'simbolo_moneda'   => 'S/',
                'color_primario'   => '#7c3aed',
                'redes_sociales'   => [],
                'costo_envio_base' => 0,
                'igv_porcentaje'   => 18,
            ]);
        }

        return response()->json($config);
    }

    // PUT /api/admin/configuracion
    public function update(Request $request)
    {
        $config = Configuracion::firstOrCreate([]);

        $config->fill([
            'nombre_tienda'       => $request->nombre_tienda       ?? $config->nombre_tienda,
            'descripcion_tienda'  => $request->descripcion_tienda  ?? $config->descripcion_tienda,
            'email_contacto'      => $request->email_contacto      ?? $config->email_contacto,
            'telefono_contacto'   => $request->telefono_contacto   ?? $config->telefono_contacto,
            'direccion'           => $request->direccion            ?? $config->direccion,
            'moneda'              => $request->moneda               ?? $config->moneda,
            'simbolo_moneda'      => $request->simbolo_moneda       ?? $config->simbolo_moneda,
            'color_primario'      => $request->color_primario       ?? $config->color_primario,
            'redes_sociales'      => $request->redes_sociales       ?? $config->redes_sociales,
            'envio_gratis_desde'  => $request->envio_gratis_desde  ?? $config->envio_gratis_desde,
            'costo_envio_base'    => $request->costo_envio_base     ?? $config->costo_envio_base,
            'igv_porcentaje'      => $request->igv_porcentaje       ?? $config->igv_porcentaje,
            'meta_titulo'         => $request->meta_titulo          ?? $config->meta_titulo,
            'meta_descripcion'    => $request->meta_descripcion     ?? $config->meta_descripcion,
        ]);

        $config->save();

        return response()->json(['message' => 'Configuración guardada', 'data' => $config]);
    }

    // POST /api/admin/configuracion/logo
    public function uploadLogo(Request $request)
    {
        $request->validate([
            'logo' => 'required|image|max:2048',
        ]);

        $config = Configuracion::firstOrCreate([]);

        // Borra el logo anterior si existe
        if ($config->logo_url) {
            $old = str_replace('/storage/', '', $config->logo_url);
            Storage::disk('public')->delete($old);
        }

        $path = $request->file('logo')->store('config', 'public');
        $config->logo_url = Storage::url($path);
        $config->save();

        return response()->json(['url' => $config->logo_url]);
    }
}
