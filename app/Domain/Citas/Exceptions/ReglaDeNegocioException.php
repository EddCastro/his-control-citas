<?php

namespace App\Domain\Citas\Exceptions;

use RuntimeException;

/**
 * Regla de negocio incumplida. La capa HTTP la traduce a JSON con su código.
 */
abstract class ReglaDeNegocioException extends RuntimeException
{
    abstract public function codigo(): string;

    public function estadoHttp(): int
    {
        return 409;
    }

    /** @return array<string, mixed> */
    public function detalle(): array
    {
        return [];
    }
}
