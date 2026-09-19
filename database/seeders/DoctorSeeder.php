<?php

namespace Database\Seeders;

use App\Models\Doctor;
use Illuminate\Database\Seeder;

class DoctorSeeder extends Seeder
{
    public function run(): void
    {
        $doctores = [
            ['colegiado' => '10231', 'nombres' => 'Luis Fernando', 'apellidos' => 'Ruiz Méndez', 'especialidad' => 'Medicina interna'],
            ['colegiado' => '11874', 'nombres' => 'María José', 'apellidos' => 'Solís Herrera', 'especialidad' => 'Pediatría'],
            ['colegiado' => '09562', 'nombres' => 'Carlos Andrés', 'apellidos' => 'Méndez Orellana', 'especialidad' => 'Cardiología'],
            ['colegiado' => '12408', 'nombres' => 'Ana Lucía', 'apellidos' => 'Castillo Ramírez', 'especialidad' => 'Ginecología'],
        ];

        foreach ($doctores as $doctor) {
            Doctor::updateOrCreate(['colegiado' => $doctor['colegiado']], $doctor + ['activo' => true]);
        }
    }
}
