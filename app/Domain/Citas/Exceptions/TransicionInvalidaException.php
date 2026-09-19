<?php

namespace App\Domain\Citas\Exceptions;

use App\Domain\Citas\EstadoCita;

/** RQF-05: el cambio de estado no está permitido desde el estado actual. */
class TransicionInvalidaException extends ReglaDeNegocioException
{
    public function __construct(private readonly EstadoCita $actual, private readonly EstadoCita $nuevo)
    {
        parent::__construct("Una cita {$actual->value} no puede pasar a {$nuevo->value}.");
    }

    public function codigo(): string
    {
        return 'TRANSICION_INVALIDA';
    }

    public function detalle(): array
    {
        return [
            'estado_actual' => $this->actual->value,
            'permitidos' => array_map(fn (EstadoCita $e) => $e->value, $this->actual->siguientes()),
        ];
    }
}
