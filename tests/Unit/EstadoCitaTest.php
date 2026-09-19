<?php

namespace Tests\Unit;

use App\Domain\Citas\EstadoCita;
use PHPUnit\Framework\TestCase;

/** Reglas de transición de estados sin base de datos (RQF-05). */
class EstadoCitaTest extends TestCase
{
    public function test_transiciones_permitidas(): void
    {
        $this->assertTrue(EstadoCita::Pendiente->puedeCambiarA(EstadoCita::Confirmada));
        $this->assertTrue(EstadoCita::Pendiente->puedeCambiarA(EstadoCita::Cancelada));
        $this->assertTrue(EstadoCita::Confirmada->puedeCambiarA(EstadoCita::Atendida));
        $this->assertTrue(EstadoCita::Confirmada->puedeCambiarA(EstadoCita::Cancelada));
    }

    public function test_transiciones_rechazadas(): void
    {
        $this->assertFalse(EstadoCita::Pendiente->puedeCambiarA(EstadoCita::Atendida));
        $this->assertFalse(EstadoCita::Pendiente->puedeCambiarA(EstadoCita::Pendiente));
        $this->assertFalse(EstadoCita::Cancelada->puedeCambiarA(EstadoCita::Confirmada));
        $this->assertFalse(EstadoCita::Atendida->puedeCambiarA(EstadoCita::Cancelada));
    }

    public function test_solo_pendiente_y_confirmada_ocupan_horario_y_se_reprograman(): void
    {
        $this->assertSame(['pendiente', 'confirmada'], EstadoCita::activos());
        $this->assertTrue(EstadoCita::Confirmada->puedeReprogramarse());
        $this->assertFalse(EstadoCita::Cancelada->puedeReprogramarse());
        $this->assertFalse(EstadoCita::Atendida->puedeReprogramarse());
    }
}
