<?php

namespace App\Repositories;

use App\Models\Cita;
use DateTimeInterface;
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

    /** Recupera la cita bloqueando su fila hasta el fin de la transacción. */
    public function buscarParaActualizar(int $id): Cita;

    /** @param array<string, mixed> $datos */
    public function crear(array $datos): Cita;

    /** @param array<string, mixed> $datos */
    public function actualizar(Cita $cita, array $datos): Cita;

    /**
     * Primera cita activa del doctor que se cruza con [inicio, fin).
     * Dos citas contiguas (una termina 10:30 y la otra empieza 10:30) no se cruzan.
     */
    public function buscarConflicto(int $doctorId, DateTimeInterface $inicio, DateTimeInterface $fin, ?int $exceptoId = null): ?Cita;

    /**
     * Serializa las escrituras sobre la agenda de un doctor (RQNF-07).
     * Debe llamarse dentro de transaccion().
     */
    public function bloquearAgendaDoctor(int $doctorId): void;

    public function registrarHistorial(Cita $cita, ?string $anterior, string $nuevo, ?string $motivo = null): void;

    /**
     * @template T
     * @param  callable(): T  $operacion
     * @return T
     */
    public function transaccion(callable $operacion): mixed;
}
