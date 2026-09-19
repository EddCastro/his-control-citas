<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Paciente */
class PacienteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nombre' => $this->nombreCompleto(),
            // Solo los últimos 4 dígitos: la lista se usa en formularios y filtros.
            'dpi' => '*********'.substr($this->dpi, -4),
            'telefono' => $this->telefono,
        ];
    }
}
