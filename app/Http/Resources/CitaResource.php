<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Cita */
class CitaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'paciente' => [
                'id' => $this->paciente->id,
                'nombre' => $this->paciente->nombreCompleto(),
            ],
            'doctor' => [
                'id' => $this->doctor->id,
                'nombre' => $this->doctor->nombreCompleto(),
                'especialidad' => $this->doctor->especialidad,
            ],
            'fecha' => $this->inicio->format('Y-m-d'),
            'hora_inicio' => $this->inicio->format('H:i'),
            'hora_fin' => $this->fin->format('H:i'),
            'inicio' => $this->inicio->format('Y-m-d\TH:i:s'),
            'fin' => $this->fin->format('Y-m-d\TH:i:s'),
            'motivo' => $this->motivo,
            'estado' => $this->estado,
            'creada' => $this->created_at?->toIso8601String(),
            'actualizada' => $this->updated_at?->toIso8601String(),
        ];
    }
}
