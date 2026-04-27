<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\EcommerceSyncJob;
use App\Services\EcommerceSyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SyncController extends Controller
{
    public function __construct(private EcommerceSyncService $syncService) {}

    /**
     * POST /api/admin/sync/run
     * Crea y ejecuta el job de forma sincrona.
     */
    public function run(Request $request): JsonResponse
    {
        $job = $this->syncService->crearJob();

        @set_time_limit(600);
        @ini_set('max_execution_time', 600);

        $job = $this->syncService->ejecutar($job);

        return response()->json([
            'success' => true,
            'data' => $job->fresh()->toArray(),
        ]);
    }

    /**
     * POST /api/admin/sync/start
     */
    public function start(): JsonResponse
    {
        $job = $this->syncService->crearJob();

        return response()->json([
            'success' => true,
            'data' => $job->toArray(),
        ]);
    }

    /**
     * POST /api/admin/sync/run-job/{id}
     */
    public function runJob(int $id): JsonResponse
    {
        $job = EcommerceSyncJob::findOrFail($id);
        if ($job->estado !== 'en_progreso') {
            return response()->json(['success' => true, 'data' => $job, 'message' => 'Job ya finalizó']);
        }

        @set_time_limit(600);
        @ini_set('max_execution_time', 600);

        $this->syncService->ejecutar($job);

        return response()->json(['success' => true, 'data' => $job->fresh()]);
    }

    /**
     * GET /api/admin/sync/status/{id}
     */
    public function status(int $id): JsonResponse
    {
        $job = EcommerceSyncJob::findOrFail($id);

        $ultimos = $job->cambios()
            ->orderByDesc('id')
            ->limit(8)
            ->get(['id', 'tipo', 'subtipo', 'sku', 'nombre', 'created_at']);

        return response()->json([
            'success' => true,
            'data' => [
                'job' => $job,
                'recientes' => $ultimos,
            ],
        ]);
    }

    /**
     * GET /api/admin/sync/{id}/cambios
     */
    public function cambios(Request $request, int $id): JsonResponse
    {
        $job = EcommerceSyncJob::findOrFail($id);

        $tipo = $request->query('tipo');
        $perPage = min((int) $request->query('per_page', 50), 200);

        $q = $job->cambios()->orderByDesc('id');
        if ($tipo) $q->where('tipo', $tipo);
        $page = $q->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => [
                'job' => $job,
                'cambios' => $page->items(),
                'paginacion' => [
                    'total' => $page->total(),
                    'por_pagina' => $page->perPage(),
                    'pagina_actual' => $page->currentPage(),
                    'ultima_pagina' => $page->lastPage(),
                ],
            ],
        ]);
    }

    /**
     * GET /api/admin/sync/last
     */
    public function last(): JsonResponse
    {
        $job = EcommerceSyncJob::orderByDesc('id')->first();
        return response()->json(['success' => true, 'data' => $job]);
    }
}
