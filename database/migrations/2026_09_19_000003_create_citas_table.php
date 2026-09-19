<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('citas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('paciente_id')->constrained('pacientes')->restrictOnDelete();
            $table->foreignId('doctor_id')->constrained('doctores')->restrictOnDelete();
            $table->dateTime('inicio');
            $table->dateTime('fin');
            $table->string('motivo', 255);
            $table->enum('estado', ['pendiente', 'confirmada', 'cancelada', 'atendida'])
                ->default('pendiente');
            $table->timestamps();

            // Consultas de agenda por doctor y rango de fechas (RQF-02, RQF-03, RQF-06).
            $table->index(['doctor_id', 'inicio', 'fin']);
            $table->index(['paciente_id', 'inicio']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('citas');
    }
};
