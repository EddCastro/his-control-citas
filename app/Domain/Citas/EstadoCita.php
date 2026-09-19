<?php

namespace App\Domain\Citas;

/**
 * Estados de una cita y sus transiciones permitidas (RQF-05, RQF-10).
 *
 *   pendiente ──► confirmada ──► atendida
 *       │              │
 *       └──► cancelada ◄┘
 *
 * cancelada y atendida son estados finales.
 */
enum EstadoCita: string
{
    case Pendiente = 'pendiente';
    case Confirmada = 'confirmada';
    case Cancelada = 'cancelada';
    case Atendida = 'atendida';

    /** @return list<string> */
    public static function valores(): array
    {
        return array_map(fn (self $e) => $e->value, self::cases());
    }

    /**
     * Estados que ocupan el horario del doctor (RQF-03).
     *
     * @return list<string>
     */
    public static function activos(): array
    {
        return [self::Pendiente->value, self::Confirmada->value];
    }

    /** @return list<self> */
    public function siguientes(): array
    {
        return match ($this) {
            self::Pendiente => [self::Confirmada, self::Cancelada],
            self::Confirmada => [self::Atendida, self::Cancelada],
            self::Cancelada, self::Atendida => [],
        };
    }

    public function puedeCambiarA(self $nuevo): bool
    {
        return in_array($nuevo, $this->siguientes(), true);
    }

    public function puedeReprogramarse(): bool
    {
        return in_array($this->value, self::activos(), true);
    }
}
