<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    /**
     * RefreshDatabase borra todas las tablas. Antes de que corra, se verifica
     * que la conexion apunte a la base de pruebas para no perder los datos
     * de his_citas.
     */
    protected function setUpTraits()
    {
        $base = DB::connection()->getDatabaseName();

        if ($base !== 'his_citas_test') {
            throw new RuntimeException("Las pruebas deben usar his_citas_test, no {$base}.");
        }

        return parent::setUpTraits();
    }
}
