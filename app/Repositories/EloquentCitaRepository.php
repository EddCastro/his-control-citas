<?php

namespace App\Repositories;

use App\Domain\Citas\EstadoCita;
use App\Models\Cita;
use App\Models\Doctor;
use App\Models\HistorialEstadoCita;
use DateTimeInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class EloquentCitaRepository implements CitaRepository
{
    public function listar(array $filtros): Collection
    {
        $consulta = Cita::query()->with(['paciente', 'doctor'])->orderBy('inicio');

        if (! empty($filtros['doctor_id'])) {
            $consulta->where('doctor_id', $filtros['doctor_id']);
        }

        if (! empty($filtros['paciente_id'])) {
            $consulta->where('paciente_id', $filtros['paciente_id']);
        }

        if (! empty($filtros['estado'])) {
            $consulta->where('estado', $filtros['estado']);
        }

        // Rango de fechas (RQF-06): citas que se cruzan con [desde, hasta).
        if (! empty($filtros['desde'])) {
            $consulta->where('fin', '>', $this->aFecha($filtros['desde']));
        }

        if (! empty($filtros['hasta'])) {
            $consulta->where('inicio', '<', $this->aFecha($filtros['hasta'], finDelDia: true));
        }

        return $consulta->get();
    }

    public function buscar(int $id): Cita
    {
        return Cita::query()->with(['paciente', 'doctor', 'historial'])->findOrFail($id);
    }

    public function buscarParaActualizar(int $id): Cita
    {
        return Cita::query()->lockForUpdate()->findOrFail($id);
    }

    public function crear(array $datos): Cita
    {
        return Cita::query()->create($datos);
    }

    public function actualizar(Cita $cita, array $datos): Cita
    {
        $cita->fill($datos)->save();

        return $cita;
    }

    public function buscarConflicto(int $doctorId, DateTimeInterface $inicio, DateTimeInterface $fin, ?int $exceptoId = null): ?Cita
    {
        return Cita::query()
            ->where('doctor_id', $doctorId)
            ->whereIn('estado', EstadoCita::activos())
            ->where('inicio', '<', $fin)
            ->where('fin', '>', $inicio)
            ->when($exceptoId, fn ($q) => $q->where('id', '!=', $exceptoId))
            ->orderBy('inicio')
            ->first();
    }

    public function bloquearAgendaDoctor(int $doctorId): void
    {
        // SELECT ... FOR UPDATE sobre la fila del doctor: una segunda solicitud para
        // el mismo doctor espera aquí hasta que la primera confirme o revierta, y al
        // continuar ya ve la cita recién creada. Doctores distintos no se bloquean.
        Doctor::query()->whereKey($doctorId)->lockForUpdate()->first();
    }

    public function registrarHistorial(Cita $cita, ?string $anterior, string $nuevo, ?string $motivo = null): void
    {
        HistorialEstadoCita::query()->create([
            'cita_id' => $cita->id,
            'estado_anterior' => $anterior,
            'estado_nuevo' => $nuevo,
            'motivo' => $motivo,
        ]);
    }

    public function transaccion(callable $operacion): mixed
    {
        return DB::transaction($operacion);
    }

    /**
     * Acepta 'Y-m-d' o una fecha y hora ISO 8601 (FullCalendar envía la segunda).
     * Una fecha sin hora usada como límite superior incluye el día completo.
     */
    private function aFecha(string $valor, bool $finDelDia = false): string
    {
        $soloFecha = preg_match('/^\d{4}-\d{2}-\d{2}$/', $valor) === 1;
        $fecha = Carbon::parse($valor)->setTimezone(config('app.timezone'));

        if ($soloFecha && $finDelDia) {
            $fecha = $fecha->addDay()->startOfDay();
        }

        return $fecha->format('Y-m-d H:i:s');
    }
}
