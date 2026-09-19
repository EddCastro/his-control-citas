<?php

namespace Database\Seeders;

use App\Models\Paciente;
use Illuminate\Database\Seeder;

class PacienteSeeder extends Seeder
{
    public function run(): void
    {
        // Datos ficticios.
        $pacientes = [
            ['dpi' => '2345678901101', 'nombres' => 'Ana Lucía', 'apellidos' => 'Pérez Arana', 'fecha_nacimiento' => '1990-04-12', 'telefono' => '5512-3344', 'correo' => 'ana.perez@example.com'],
            ['dpi' => '2456789012101', 'nombres' => 'Carlos Manuel', 'apellidos' => 'Gómez López', 'fecha_nacimiento' => '1985-09-03', 'telefono' => '5523-4455', 'correo' => 'carlos.gomez@example.com'],
            ['dpi' => '2567890123101', 'nombres' => 'Lucía Teresa', 'apellidos' => 'Ramírez Cano', 'fecha_nacimiento' => '2016-01-25', 'telefono' => '5534-5566', 'correo' => 'lucia.ramirez@example.com'],
            ['dpi' => '2678901234101', 'nombres' => 'Mario Alberto', 'apellidos' => 'Cifuentes Paz', 'fecha_nacimiento' => '1972-11-30', 'telefono' => '5545-6677', 'correo' => 'mario.cifuentes@example.com'],
            ['dpi' => '2789012345101', 'nombres' => 'José Rodrigo', 'apellidos' => 'López Estrada', 'fecha_nacimiento' => '1968-06-18', 'telefono' => '5556-7788', 'correo' => 'jose.lopez@example.com'],
            ['dpi' => '2890123456101', 'nombres' => 'Sofía Alejandra', 'apellidos' => 'Morales Díaz', 'fecha_nacimiento' => '1995-02-07', 'telefono' => '5567-8899', 'correo' => 'sofia.morales@example.com'],
            ['dpi' => '2901234567101', 'nombres' => 'Diego Andrés', 'apellidos' => 'Hernández Juárez', 'fecha_nacimiento' => '2012-08-21', 'telefono' => '5578-9900', 'correo' => 'diego.hernandez@example.com'],
            ['dpi' => '3012345678101', 'nombres' => 'Gabriela', 'apellidos' => 'Fuentes Aguilar', 'fecha_nacimiento' => '1988-12-02', 'telefono' => '5589-0011', 'correo' => 'gabriela.fuentes@example.com'],
        ];

        foreach ($pacientes as $paciente) {
            Paciente::updateOrCreate(['dpi' => $paciente['dpi']], $paciente);
        }
    }
}
