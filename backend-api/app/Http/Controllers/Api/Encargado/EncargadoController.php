<?php

namespace App\Http\Controllers\Api\Encargado;

use App\Http\Controllers\Controller;
use App\Http\Requests\Encargado\EncargadoStoreRequest;
use App\Http\Resources\Encargado\EncargadoResource;
use App\Services\EncargadoService;
use Illuminate\Http\JsonResponse;
use Throwable;

class EncargadoController extends Controller
{
    public function __construct(private EncargadoService $service) {}

    public function store(EncargadoStoreRequest $request): JsonResponse
    {
        try {
            $result = $this->service->create($request->validated());

            return response()->json([
                'message' => 'Encargado asignado correctamente.',
                'data'    => new EncargadoResource($result['encargo'], $result['user'], $result['plain_password']),
            ], 201);
        } catch (Throwable $e) {
            report($e);
            return response()->json([
                'message' => 'No se pudo crear/asignar el Encargado.',
                'error'   => $e->getMessage(),
            ], 422);
        }
    }
}
