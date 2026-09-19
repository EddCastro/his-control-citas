<?php

namespace App\Services;

use App\Domain\Citas\EstadoCita;
use App\Domain\Citas\Exceptions\CitaNoReprogramableException;
use App\Domain\Citas\Exceptions\ConflictoHorarioException;
use App\Domain\Citas\Exceptions\TransicionInvalidaException;
use App\Models\Cita;
use App\Repositories\CitaRepository;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Lógica de negocio de citas (RQNF-04).
 *
 * El controlador HTTP solo traduce la petición y la respuesta; las reglas viven aquí.
 * La validación de disponibilidad se ejecuta siempre en el servidor (RQNF-07).
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
     * RQF-01 y RQF-03: crea una cita pendiente si el doctor está libre.
     *
     * @param  array{paciente_id: int, doctor_id: int, fecha: string, hora_inicio: string, hora_fin: string, motivo: string}  $datos
     *
     * @throws ConflictoHorarioException
     */
    public function crear(array $datos): Cita
    {
        [$inicio, $fin] = $this->intervalo($datos['fecha'], $datos['hora_inicio'], $datos['hora_fin']);
        $doctorId = (int) $datos['doctor_id'];

        $cita = $this->citas->transaccion(function () use ($datos, $doctorId, $inicio, $fin) {
            $this->citas->bloquearAgendaDoctor($doctorId);
            $this->asegurarHorarioLibre($doctorId, $inicio, $fin);

            $cita = $this->citas->crear([
                'paciente_id' => $datos['paciente_id'],
                'doctor_id' => $doctorId,
                'inicio' => $inicio,
                'fin' => $fin,
                'motivo' => $datos['motivo'],
                'estado' => EstadoCita::Pendiente->value,
            ]);
            $this->citas->registrarHistorial($cita, null, EstadoCita::Pendiente->value, 'Cita creada');

            return $cita;
        });

        return $this->citas->buscar($cita->id);
    }

    /**
     * RQF-04 y RQF-03: mueve una cita activa a un horario libre del mismo doctor.
     *
     * @param  array{fecha: string, hora_inicio: string, hora_fin: string}  $datos
     *
     * @throws CitaNoReprogramableException
     * @throws ConflictoHorarioException
     */
    public function reprogramar(Cita $cita, array $datos): Cita
    {
        [$inicio, $fin] = $this->intervalo($datos['fecha'], $datos['hora_inicio'], $datos['hora_fin']);

        $this->citas->transaccion(function () use ($cita, $inicio, $fin) {
            $this->citas->bloquearAgendaDoctor($cita->doctor_id);
            $actual = $this->citas->buscarParaActualizar($cita->id);

            if (! EstadoCita::from($actual->estado)->puedeReprogramarse()) {
                throw new CitaNoReprogramableException($actual->estado);
            }

            $this->asegurarHorarioLibre($actual->doctor_id, $inicio, $fin, exceptoId: $actual->id);
            $this->citas->actualizar($actual, ['inicio' => $inicio, 'fin' => $fin]);
        });

        return $this->citas->buscar($cita->id);
    }

    /**
     * RQF-05: cambia el estado respetando las transiciones y conserva el historial.
     * La cita se lee bloqueada, así dos usuarios no pueden confirmar y cancelar a la vez.
     *
     * @throws TransicionInvalidaException
     */
    public function cambiarEstado(Cita $cita, EstadoCita $nuevo, ?string $motivo = null): Cita
    {
        $this->citas->transaccion(function () use ($cita, $nuevo, $motivo) {
            $actual = $this->citas->buscarParaActualizar($cita->id);
            $estadoActual = EstadoCita::from($actual->estado);

            if (! $estadoActual->puedeCambiarA($nuevo)) {
                throw new TransicionInvalidaException($estadoActual, $nuevo);
            }

            $this->citas->actualizar($actual, ['estado' => $nuevo->value]);
            $this->citas->registrarHistorial($actual, $estadoActual->value, $nuevo->value, $motivo);
        });

        return $this->citas->buscar($cita->id);
    }

    /** @throws ConflictoHorarioException */
    private function asegurarHorarioLibre(int $doctorId, Carbon $inicio, Carbon $fin, ?int $exceptoId = null): void
    {
        $conflicto = $this->citas->buscarConflicto($doctorId, $inicio, $fin, $exceptoId);

        if ($conflicto !== null) {
            throw new ConflictoHorarioException($conflicto);
        }
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
