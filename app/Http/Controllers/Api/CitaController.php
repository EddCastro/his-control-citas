<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CambiarEstadoCitaRequest;
use App\Http\Requests\Api\ListarCitasRequest;
use App\Http\Requests\Api\ReprogramarCitaRequest;
use App\Http\Requests\Api\StoreCitaRequest;
use App\Http\Resources\CitaResource;
use App\Services\CitaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * API REST de citas (RQF-07).
 *
 * Solo traduce HTTP: valida con FormRequest, delega en CitaService y
 * responde con CitaResource. No contiene reglas de negocio (RQNF-04).
 */
class CitaController extends Controller
{
    public function __construct(
        private readonly CitaService $servicio,
    ) {}

    /** GET /api/citas — RQF-02, RQF-06 */
    public function index(ListarCitasRequest $request): AnonymousResourceCollection
    {
        return CitaResource::collection($this->servicio->listar($request->filtros()));
    }

    /** POST /api/citas — RQF-01 */
    public function store(StoreCitaRequest $request): JsonResponse
    {
        $cita = $this->servicio->crear($request->validated());

        return CitaResource::make($cita)->response()->setStatusCode(201);
    }

    /** GET /api/citas/{id} — RQF-09 */
    public function show(int $id): CitaResource
    {
        return CitaResource::make($this->servicio->obtener($id));
    }

    /** PUT /api/citas/{id} — RQF-04 */
    public function update(ReprogramarCitaRequest $request, int $id): CitaResource
    {
        $cita = $this->servicio->obtener($id);

        return CitaResource::make($this->servicio->reprogramar($cita, $request->validated()));
    }

    /** PATCH /api/citas/{id}/estado — RQF-05 */
    public function cambiarEstado(CambiarEstadoCitaRequest $request, int $id): CitaResource
    {
        $cita = $this->servicio->obtener($id);

        return CitaResource::make($this->servicio->cambiarEstado($cita, $request->estado()));
    }
}
