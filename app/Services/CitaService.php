<?php

namespace App\Services;

use App\Domain\Citas\EstadoCita;
use App\Models\Cita;
use App\Repositories\CitaRepository;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Lógica de negocio de citas (RQNF-04).
 *
 * El controlador HTTP solo traduce la petición y la respuesta; las reglas viven aquí.
 */
class CitaService
{
    public function __construct(
        private readonly CitaRepository $citas,
    ) {}

    /** @return Collection<int, Cita> */
    public function listar(array $filtros): Collection
    {
        return $this->citas->listar($filtros);
    }

    public function obtener(int $id): Cita
    {
        return $this->citas->buscar($id);
    }

    /**
     * RQF-01: crea una cita en estado pendiente.
     *
     * @param  array{paciente_id: int, doctor_id: int, fecha: string, hora_inicio: string, hora_fin: string, motivo: string}  $datos
     */
    public function crear(array $datos): Cita
    {
        [$inicio, $fin] = $this->intervalo($datos['fecha'], $datos['hora_inicio'], $datos['hora_fin']);

        return $this->citas->crear([
            'paciente_id' => $datos['paciente_id'],
            'doctor_id' => $datos['doctor_id'],
            'inicio' => $inicio,
            'fin' => $fin,
            'motivo' => $datos['motivo'],
            'estado' => EstadoCita::Pendiente->value,
        ]);
    }

    /**
     * RQF-04: cambia la fecha y hora de una cita existente.
     *
     * @param  array{fecha: string, hora_inicio: string, hora_fin: string}  $datos
     */
    public function reprogramar(Cita $cita, array $datos): Cita
    {
        [$inicio, $fin] = $this->intervalo($datos['fecha'], $datos['hora_inicio'], $datos['hora_fin']);

        return $this->citas->actualizar($cita, ['inicio' => $inicio, 'fin' => $fin]);
    }

    /**
     * RQF-05: cambia el estado sin eliminar el registro.
     */
    public function cambiarEstado(Cita $cita, EstadoCita $nuevo): Cita
    {
        return $this->citas->actualizar($cita, ['estado' => $nuevo->value]);
    }

    /** @return array{0: Carbon, 1: Carbon} */
    private function intervalo(string $fecha, string $horaInicio, string $horaFin): array
    {
        return [
            Carbon::parse("{$fecha} {$horaInicio}"),
            Carbon::parse("{$fecha} {$horaFin}"),
        ];
    }
}
