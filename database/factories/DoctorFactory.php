<?php

namespace Database\Factories;

use App\Models\Doctor;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Doctor> */
class DoctorFactory extends Factory
{
    protected $model = Doctor::class;

    public function definition(): array
    {
        return [
            'nombres' => fake()->firstName(),
            'apellidos' => fake()->lastName(),
            'especialidad' => fake()->randomElement(['Medicina interna', 'Pediatría', 'Cardiología', 'Ginecología']),
            'colegiado' => fake()->unique()->numerify('#####'),
            'activo' => true,
        ];
    }
}
