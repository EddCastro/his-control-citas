<?php

namespace App\Repositories;

use App\Models\Cita;
use Illuminate\Support\Collection;

/**
 * Acceso a datos de citas (RQNF-04).
 *
 * La lógica de negocio depende de este contrato y no de Eloquent directamente.
 */
interface CitaRepository
{
    /**
     * @param  array{doctor_id?: int|null, paciente_id?: int|null, desde?: string|null, hasta?: string|null, estado?: string|null}  $filtros
     * @return Collection<int, Cita>
     */
    public function listar(array $filtros): Collection;

    public function buscar(int $id): Cita;

    /** @param array<string, mixed> $datos */
    public function crear(array $datos): Cita;

    /** @param array<string, mixed> $datos */
    public function actualizar(Cita $cita, array $datos): Cita;
}
