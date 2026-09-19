<?php

namespace Database\Seeders;

use App\Models\Cita;
use App\Models\Doctor;
use App\Models\HistorialEstadoCita;
use App\Models\Paciente;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class CitaSeeder extends Seeder
{
    /**
     * Citas de ejemplo en la semana actual y la siguiente, sin cruces por doctor.
     * Solo se insertan si la tabla está vacía, para que el seeder sea idempotente.
     */
    public function run(): void
    {
        if (Cita::query()->exists()) {
            return;
        }

        $doctores = Doctor::orderBy('id')->pluck('id')->all();
        $pacientes = Paciente::orderBy('id')->pluck('id')->all();
        $lunes = Carbon::now()->startOfWeek(Carbon::MONDAY);

        // [día desde el lunes, hora, minutos de duración, doctor, paciente, estado, motivo]
        $plan = [
            [0, '08:00', 30, 0, 0, 'atendida', 'Control de presión arterial'],
            [0, '09:00', 30, 1, 2, 'confirmada', 'Control de niño sano'],
            [1, '10:00', 45, 2, 3, 'confirmada', 'Evaluación cardiológica'],
            [1, '10:00', 30, 0, 1, 'pendiente', 'Seguimiento de laboratorio'],
            [2, '14:00', 30, 3, 5, 'pendiente', 'Control prenatal'],
            [2, '15:00', 30, 1, 6, 'cancelada', 'Vacunación'],
            [3, '08:30', 30, 0, 4, 'pendiente', 'Dolor de espalda'],
            [4, '11:00', 60, 2, 7, 'pendiente', 'Electrocardiograma'],
            [7, '09:00', 30, 0, 0, 'pendiente', 'Revisión de resultados'],
            [8, '10:30', 30, 1, 2, 'pendiente', 'Fiebre persistente'],
            [9, '16:00', 30, 3, 5, 'confirmada', 'Ultrasonido de control'],
        ];

        foreach ($plan as [$dia, $hora, $min, $d, $p, $estado, $motivo]) {
            $inicio = $lunes->copy()->addDays($dia)->setTimeFromTimeString($hora);

            $cita = Cita::create([
                'doctor_id' => $doctores[$d],
                'paciente_id' => $pacientes[$p],
                'inicio' => $inicio,
                'fin' => $inicio->copy()->addMinutes($min),
                'motivo' => $motivo,
                'estado' => $estado,
            ]);

            // Historial coherente con el estado sembrado (RQF-05).
            HistorialEstadoCita::create(['cita_id' => $cita->id, 'estado_anterior' => null, 'estado_nuevo' => 'pendiente', 'motivo' => 'Cita creada']);

            if ($estado !== 'pendiente') {
                $anterior = $estado === 'atendida' ? 'confirmada' : 'pendiente';
                if ($estado === 'atendida') {
                    HistorialEstadoCita::create(['cita_id' => $cita->id, 'estado_anterior' => 'pendiente', 'estado_nuevo' => 'confirmada', 'motivo' => null]);
                }
                HistorialEstadoCita::create(['cita_id' => $cita->id, 'estado_anterior' => $anterior, 'estado_nuevo' => $estado, 'motivo' => $estado === 'cancelada' ? 'El paciente reprogramará' : null]);
            }
        }
    }
}
