<?php

namespace Database\Factories;

use App\Models\Paciente;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Paciente> */
class PacienteFactory extends Factory
{
    protected $model = Paciente::class;

    public function definition(): array
    {
        return [
            'nombres' => fake()->firstName(),
            'apellidos' => fake()->lastName().' '.fake()->lastName(),
            'dpi' => fake()->unique()->numerify('#############'),
            'fecha_nacimiento' => fake()->date('Y-m-d', '-18 years'),
            'telefono' => fake()->numerify('5###-####'),
            'correo' => fake()->unique()->safeEmail(),
        ];
    }
}
