<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Datos semilla mínimos: doctores, pacientes y citas.
     * Se puede ejecutar varias veces sin duplicar registros.
     */
    public function run(): void
    {
        $this->call([
            DoctorSeeder::class,
            PacienteSeeder::class,
            CitaSeeder::class,
        ]);
    }
}
