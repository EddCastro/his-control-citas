<?php

namespace App\Repositories;

use App\Models\Cita;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

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
        return Cita::query()->with(['paciente', 'doctor'])->findOrFail($id);
    }

    public function crear(array $datos): Cita
    {
        return Cita::query()->create($datos)->load(['paciente', 'doctor']);
    }

    public function actualizar(Cita $cita, array $datos): Cita
    {
        $cita->fill($datos)->save();

        return $cita->load(['paciente', 'doctor']);
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
