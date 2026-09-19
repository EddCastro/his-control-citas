<?php

namespace App\Domain\Citas\Exceptions;

/** Una cita cancelada o atendida ya no se puede mover en el calendario. */
class CitaNoReprogramableException extends ReglaDeNegocioException
{
    public function __construct(string $estado)
    {
        parent::__construct("Una cita {$estado} no se puede reprogramar.");
    }

    public function codigo(): string
    {
        return 'CITA_NO_REPROGRAMABLE';
    }
}
