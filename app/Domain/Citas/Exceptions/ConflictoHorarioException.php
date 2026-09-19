<?php

namespace App\Domain\Citas\Exceptions;

use App\Models\Cita;

/** RQF-03: el doctor ya tiene una cita activa que se cruza con el intervalo. */
class ConflictoHorarioException extends ReglaDeNegocioException
{
    public function __construct(private readonly Cita $existente)
    {
        parent::__construct(sprintf(
            'El doctor ya tiene una cita %s de %s a %s el %s.',
            $existente->estado,
            $existente->inicio->format('H:i'),
            $existente->fin->format('H:i'),
            $existente->inicio->format('d/m/Y'),
        ));
    }

    public function codigo(): string
    {
        return 'CONFLICTO_HORARIO';
    }

    public function detalle(): array
    {
        return [
            'cita_en_conflicto' => [
                'id' => $this->existente->id,
                'inicio' => $this->existente->inicio->format('Y-m-d\TH:i:s'),
                'fin' => $this->existente->fin->format('Y-m-d\TH:i:s'),
                'estado' => $this->existente->estado,
            ],
        ];
    }
}
