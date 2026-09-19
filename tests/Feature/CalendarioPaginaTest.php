<?php

namespace Tests\Feature;

use Tests\TestCase;

class CalendarioPaginaTest extends TestCase
{
    public function test_la_pagina_principal_carga_el_calendario_rqf_02(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('id="calendario"', false)
            ->assertSee('vendor/fullcalendar-6.1.21/index.global.min.js', false)
            ->assertSee('js/calendario.js', false);
    }

    public function test_los_recursos_del_calendario_existen(): void
    {
        foreach (['js/calendario.js', 'js/api-citas.js', 'js/mapeo-eventos.js', 'css/calendario.css', 'vendor/fullcalendar-6.1.21/index.global.min.js'] as $archivo) {
            $this->assertFileExists(public_path($archivo));
        }
    }
}
