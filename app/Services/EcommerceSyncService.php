<?php

namespace App\Services;

use App\Models\Categoria;
use App\Models\EcommerceSyncCambio;
use App\Models\EcommerceSyncJob;
use App\Models\Marca;
use App\Models\Producto;
use App\Models\ProductoImagen;
use App\Models\ProductoTalla;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class EcommerceSyncService
{
    protected string $baseUrl;
    public int $totalRequests = 0;

    public function __construct()
    {
        $this->baseUrl = (string) config('services.ecommerce.url', env('ECOMMERCE_API_URL', 'https://api1.eless.com.pe/api/v1'));
    }

    public function crearJob(?int $userId = null): EcommerceSyncJob
    {
        return EcommerceSyncJob::create([
            'codigo' => 'syc_' . Str::lower(Str::random(10)),
            'estado' => 'en_progreso',
            'iniciado_at' => now(),
            'fase_actual' => 'Iniciando',
            'iniciado_por' => $userId,
        ]);
    }

    public function ejecutar(EcommerceSyncJob $job): EcommerceSyncJob
    {
        try {
            $this->actualizarFase($job, 'Sincronizando marcas y categorías', 5);
            $this->sincronizarMarcas();
            $this->sincronizarCategorias();

            $this->actualizarFase($job, 'Listando catálogo', 10);
            $allProductos = $this->descargarTodosProductos($job);

            $job->total_productos = count($allProductos);
            $job->save();

            $existentesLocal = Producto::pluck('ecommerce_id')->mapWithKeys(fn($id) => [$id => true])->all();
            $idsRemotos = [];

            $i = 0;
            $total = max(1, count($allProductos));
            foreach ($allProductos as $raw) {
                $i++;
                $idsRemotos[$raw['id']] = true;

                $this->upsertProducto($job, $raw, $existentesLocal);

                $job->procesados = $i;
                $job->progreso_pct = min(95, 10 + (int) (($i / $total) * 80));
                $job->fase_actual = "Procesando productos ({$i}/{$total})";
                $job->save();
            }

            $this->actualizarFase($job, 'Detectando productos removidos', 96);
            $removidosCount = $this->marcarRemovidos($job, $existentesLocal, $idsRemotos);
            $job->removidos = $removidosCount;

            $this->actualizarFase($job, 'Finalizando', 100);
            $job->estado = 'completado';
            $job->terminado_at = now();
            $job->duracion_segundos = $job->iniciado_at->diffInSeconds($job->terminado_at);
            $job->total_requests_http = $this->totalRequests;
            $job->fase_actual = 'Completada';
            $job->save();
        } catch (\Throwable $e) {
            Log::error('EcommerceSync error', ['msg' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            $job->estado = 'error';
            $job->terminado_at = now();
            $job->duracion_segundos = $job->iniciado_at?->diffInSeconds($job->terminado_at);
            $job->error_mensaje = $e->getMessage();
            $job->save();
        }

        return $job->fresh();
    }

    private function actualizarFase(EcommerceSyncJob $job, string $fase, int $pct): void
    {
        $job->fase_actual = $fase;
        $job->progreso_pct = $pct;
        $job->save();
    }

    private function descargarTodosProductos(EcommerceSyncJob $job): array
    {
        $all = [];
        $page = 1;
        $perPage = 50;
        $maxPages = 100;

        while ($page <= $maxPages) {
            $resp = Http::timeout(30)
                ->acceptJson()
                ->get($this->baseUrl . '/productos', [
                    'per_page' => $perPage,
                    'page' => $page,
                ]);
            $this->totalRequests++;

            if (!$resp->successful()) {
                throw new \RuntimeException("Error HTTP {$resp->status()} listando productos pagina={$page}");
            }
            $body = $resp->json();
            $items = data_get($body, 'data.productos', []);
            $all = array_merge($all, $items);

            $ultimaPagina = (int) data_get($body, 'data.paginacion.ultima_pagina', 1);
            if ($page >= $ultimaPagina || count($items) === 0) break;
            $page++;
        }

        return $all;
    }

    private function sincronizarMarcas(): void
    {
        $resp = Http::timeout(20)->acceptJson()->get($this->baseUrl . '/marcas');
        $this->totalRequests++;
        if (!$resp->successful()) return;

        foreach ((array) $resp->json('data', []) as $m) {
            Marca::updateOrCreate(
                ['ecommerce_id' => $m['id']],
                [
                    'nombre' => $m['nombre'],
                    'slug' => $m['slug'] ?? Str::slug($m['nombre']),
                    'logo' => $m['logo'] ?? null,
                    'productos_count' => $m['productos_count'] ?? 0,
                ],
            );
        }
    }

    private function sincronizarCategorias(): void
    {
        $resp = Http::timeout(20)->acceptJson()->get($this->baseUrl . '/categorias');
        $this->totalRequests++;
        if (!$resp->successful()) return;

        foreach ((array) $resp->json('data', []) as $c) {
            Categoria::updateOrCreate(
                ['ecommerce_id' => $c['id']],
                [
                    'nombre' => $c['nombre'],
                    'slug' => $c['slug'] ?? Str::slug($c['nombre']),
                    'descripcion' => $c['descripcion'] ?? null,
                    'imagen' => $c['imagen'] ?? null,
                    'padre_ecommerce_id' => $c['padre_id'] ?? null,
                    'productos_count' => $c['productos_count'] ?? 0,
                ],
            );
        }
    }

    private function upsertProducto(EcommerceSyncJob $job, array $raw, array $existentesLocal): void
    {
        $ecommerceId = (int) $raw['id'];
        $existente = Producto::where('ecommerce_id', $ecommerceId)->with('tallas')->first();
        $marca = !empty($raw['marca']) ? Marca::where('nombre', $raw['marca'])->first() : null;

        $catNombre = $raw['categoria'] ?? null;
        $catSlug = $raw['categoria_slug'] ?? null;

        $payload = [
            'nombre' => $raw['nombre'] ?? '',
            'slug' => $raw['slug'] ?? Str::slug($raw['nombre'] ?? ''),
            'sku' => $raw['sku'] ?? '',
            'descripcion_corta' => $raw['descripcion_corta'] ?? null,
            'descripcion' => $raw['descripcion'] ?? null,
            'precio' => (float) ($raw['precio'] ?? 0),
            'precio_comparacion' => isset($raw['precio_comparacion']) ? (float) $raw['precio_comparacion'] : null,
            'marca_id' => $marca?->id,
            'marca_nombre' => $raw['marca'] ?? null,
            'categoria_principal' => $catNombre,
            'categoria_slug' => $catSlug,
            'color' => $raw['color'] ?? null,
            'color_hex' => $raw['color_hex'] ?? null,
            'genero' => data_get($raw, 'generos.0.id'),
            'video_url' => $raw['video_url'] ?? null,
            'imagen_principal' => $raw['imagen'] ?? null,
            'en_stock' => (bool) ($raw['en_stock'] ?? false),
            'stock_total' => (int) ($raw['stock_total'] ?? 0),
            'activo' => true,
            'nuevo' => (bool) ($raw['nuevo'] ?? false),
            'destacado' => (bool) ($raw['destacado'] ?? false),
            'ultima_sync_at' => now(),
            'ecommerce_raw' => $raw,
        ];

        DB::transaction(function () use ($job, $ecommerceId, $existente, $payload, $raw) {
            if (!$existente) {
                $producto = Producto::create(array_merge($payload, ['ecommerce_id' => $ecommerceId]));
                $job->nuevos++;
                $job->save();
                $this->sincronizarTallasYImagenes($producto, $raw);

                EcommerceSyncCambio::create([
                    'sync_id' => $job->id,
                    'tipo' => 'nuevo',
                    'producto_id' => $producto->id,
                    'ecommerce_id' => $ecommerceId,
                    'sku' => $producto->sku,
                    'nombre' => $producto->nombre,
                    'despues' => ['precio' => $producto->precio, 'stock' => $producto->stock_total],
                ]);
            } else {
                $diffs = $this->diffPayload($existente, $payload);
                $existente->fill($payload)->save();
                $this->sincronizarTallasYImagenes($existente, $raw);

                if (!empty($diffs)) {
                    $job->actualizados++;
                    $job->save();
                    foreach ($diffs as $subtipo => $par) {
                        EcommerceSyncCambio::create([
                            'sync_id' => $job->id,
                            'tipo' => 'actualizado',
                            'subtipo' => $subtipo,
                            'producto_id' => $existente->id,
                            'ecommerce_id' => $ecommerceId,
                            'sku' => $existente->sku,
                            'nombre' => $existente->nombre,
                            'antes' => ['v' => $par[0]],
                            'despues' => ['v' => $par[1]],
                        ]);
                    }
                } else {
                    $job->sin_cambios++;
                    $job->save();
                }
            }
        });
    }

    private function diffPayload(Producto $p, array $next): array
    {
        $diffs = [];
        if ((float) $p->precio !== (float) $next['precio']) {
            $diffs['precio'] = [(float) $p->precio, (float) $next['precio']];
        }
        if ((int) $p->stock_total !== (int) $next['stock_total']) {
            $diffs['stock'] = [(int) $p->stock_total, (int) $next['stock_total']];
        }
        if ($p->nombre !== $next['nombre']) {
            $diffs['nombre'] = [$p->nombre, $next['nombre']];
        }
        if ((string) $p->imagen_principal !== (string) ($next['imagen_principal'] ?? '')) {
            $diffs['imagen'] = [$p->imagen_principal, $next['imagen_principal']];
        }
        if ((bool) $p->activo !== (bool) $next['activo']) {
            $diffs['estado'] = [$p->activo, $next['activo']];
        }
        return $diffs;
    }

    private function sincronizarTallasYImagenes(Producto $producto, array $raw): void
    {
        ProductoTalla::where('producto_id', $producto->id)->delete();
        foreach ((array) ($raw['tallas'] ?? []) as $t) {
            ProductoTalla::create([
                'producto_id' => $producto->id,
                'talla' => $t['talla'] ?? '',
                'ajuste_precio' => (float) ($t['ajuste_precio'] ?? 0),
                'precio_final' => (float) ($t['precio_final'] ?? 0),
                'stock' => (int) ($t['stock'] ?? 0),
                'es_predeterminada' => (bool) ($t['es_predeterminada'] ?? false),
            ]);
        }

        ProductoImagen::where('producto_id', $producto->id)->delete();
        $orden = 0;
        foreach ((array) ($raw['imagenes'] ?? []) as $img) {
            ProductoImagen::create([
                'producto_id' => $producto->id,
                'url' => $img['url'] ?? '',
                'alt' => $img['alt'] ?? null,
                'es_principal' => (bool) ($img['es_principal'] ?? false),
                'orden' => $orden++,
            ]);
        }
    }

    private function marcarRemovidos(EcommerceSyncJob $job, array $existentesLocal, array $idsRemotos): int
    {
        $count = 0;
        foreach ($existentesLocal as $ecommerceId => $_) {
            if (!isset($idsRemotos[$ecommerceId])) {
                $p = Producto::where('ecommerce_id', $ecommerceId)->first();
                if ($p && $p->activo) {
                    $p->activo = false;
                    $p->save();
                    EcommerceSyncCambio::create([
                        'sync_id' => $job->id,
                        'tipo' => 'removido',
                        'subtipo' => 'inactivo',
                        'producto_id' => $p->id,
                        'ecommerce_id' => $ecommerceId,
                        'sku' => $p->sku,
                        'nombre' => $p->nombre,
                        'antes' => ['activo' => true],
                        'despues' => ['activo' => false],
                    ]);
                    $count++;
                }
            }
        }
        return $count;
    }
}
