<?php

namespace App\Http\Resources;

use App\Domain\Citas\EstadoCita;
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
            // La interfaz ofrece solo las acciones que el servidor permite.
            'transiciones' => array_map(fn (EstadoCita $e) => $e->value, EstadoCita::from($this->estado)->siguientes()),
            'reprogramable' => EstadoCita::from($this->estado)->puedeReprogramarse(),
            'historial' => $this->whenLoaded('historial', fn () => $this->historial->map(fn ($h) => [
                'estado_anterior' => $h->estado_anterior,
                'estado_nuevo' => $h->estado_nuevo,
                'motivo' => $h->motivo,
                'fecha' => $h->created_at?->toIso8601String(),
            ])),
            'creada' => $this->created_at?->toIso8601String(),
            'actualizada' => $this->updated_at?->toIso8601String(),
        ];
    }
}
