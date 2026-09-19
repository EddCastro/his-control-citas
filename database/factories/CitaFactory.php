<?php

namespace Database\Factories;

use App\Models\Cita;
use App\Models\Doctor;
use App\Models\Paciente;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Cita> */
class CitaFactory extends Factory
{
    protected $model = Cita::class;

    public function definition(): array
    {
        $inicio = now()->addDays(2)->setTime(10, 0);

        return [
            'paciente_id' => Paciente::factory(),
            'doctor_id' => Doctor::factory(),
            'inicio' => $inicio,
            'fin' => $inicio->copy()->addMinutes(30),
            'motivo' => 'Consulta general',
            'estado' => 'pendiente',
        ];
    }
}
