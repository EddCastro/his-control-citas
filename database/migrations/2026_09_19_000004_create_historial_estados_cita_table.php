<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Bitácora de cambios de estado: una cita cancelada no se elimina y cada
     * transición queda registrada con su motivo (RQF-05).
     */
    public function up(): void
    {
        Schema::create('historial_estados_cita', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cita_id')->constrained('citas')->cascadeOnDelete();
            $table->enum('estado_anterior', ['pendiente', 'confirmada', 'cancelada', 'atendida'])->nullable();
            $table->enum('estado_nuevo', ['pendiente', 'confirmada', 'cancelada', 'atendida']);
            $table->string('motivo', 255)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['cita_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('historial_estados_cita');
    }
};
