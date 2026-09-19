<?php

namespace Tests\Feature\Api;

use App\Models\Cita;
use App\Models\Doctor;
use App\Models\Paciente;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConflictosYEstadosTest extends TestCase
{
    use RefreshDatabase;

    private Doctor $doctor;

    private Paciente $paciente;

    private string $fecha;

    protected function setUp(): void
    {
        parent::setUp();
        $this->doctor = Doctor::factory()->create();
        $this->paciente = Paciente::factory()->create();
        $this->fecha = now()->addDays(2)->format('Y-m-d');
    }

    private function crear(string $inicio, string $fin, ?Doctor $doctor = null)
    {
        return $this->postJson('/api/citas', [
            'paciente_id' => $this->paciente->id,
            'doctor_id' => ($doctor ?? $this->doctor)->id,
            'fecha' => $this->fecha,
            'hora_inicio' => $inicio,
            'hora_fin' => $fin,
            'motivo' => 'Consulta',
        ]);
    }

    // ---------- RQF-03 / RQNF-07: doble reserva ----------

    public function test_mismo_horario_del_mismo_doctor_responde_409(): void
    {
        $this->crear('10:00', '10:30')->assertCreated();

        $this->crear('10:00', '10:30')
            ->assertStatus(409)
            ->assertJsonPath('code', 'CONFLICTO_HORARIO')
            ->assertJsonStructure(['message', 'cita_en_conflicto' => ['id', 'inicio', 'fin', 'estado']]);

        $this->assertDatabaseCount('citas', 1);
    }

    public function test_un_solape_parcial_tambien_responde_409(): void
    {
        $this->crear('10:00', '11:00')->assertCreated();

        $this->crear('10:30', '11:30')->assertStatus(409);
        $this->crear('09:30', '10:15')->assertStatus(409);
        $this->crear('10:15', '10:45')->assertStatus(409);
    }

    public function test_citas_contiguas_no_se_cruzan(): void
    {
        $this->crear('10:00', '10:30')->assertCreated();
        $this->crear('10:30', '11:00')->assertCreated();
        $this->crear('09:30', '10:00')->assertCreated();
    }

    public function test_otro_doctor_puede_usar_el_mismo_horario(): void
    {
        $this->crear('10:00', '10:30')->assertCreated();
        $this->crear('10:00', '10:30', Doctor::factory()->create())->assertCreated();
    }

    public function test_una_cita_cancelada_libera_el_horario(): void
    {
        $id = $this->crear('10:00', '10:30')->json('data.id');
        $this->patchJson("/api/citas/{$id}/estado", ['estado' => 'cancelada', 'motivo' => 'No asistirá'])->assertOk();

        $this->crear('10:00', '10:30')->assertCreated();
    }

    public function test_reprogramar_hacia_un_horario_ocupado_responde_409(): void
    {
        $this->crear('10:00', '10:30')->assertCreated();
        $id = $this->crear('11:00', '11:30')->json('data.id');

        $this->putJson("/api/citas/{$id}", ['fecha' => $this->fecha, 'hora_inicio' => '10:15', 'hora_fin' => '10:45'])
            ->assertStatus(409)
            ->assertJsonPath('code', 'CONFLICTO_HORARIO');

        $this->assertDatabaseHas('citas', ['id' => $id, 'inicio' => "{$this->fecha} 11:00:00"]);
    }

    public function test_reprogramar_sobre_su_propio_horario_no_es_conflicto(): void
    {
        $id = $this->crear('10:00', '10:30')->json('data.id');

        $this->putJson("/api/citas/{$id}", ['fecha' => $this->fecha, 'hora_inicio' => '10:15', 'hora_fin' => '10:45'])
            ->assertOk()
            ->assertJsonPath('data.hora_inicio', '10:15');
    }

    // ---------- RQF-05: estados ----------

    public function test_flujo_completo_pendiente_confirmada_atendida(): void
    {
        $id = $this->crear('10:00', '10:30')->json('data.id');

        $this->patchJson("/api/citas/{$id}/estado", ['estado' => 'confirmada'])->assertOk()->assertJsonPath('data.estado', 'confirmada');
        $this->patchJson("/api/citas/{$id}/estado", ['estado' => 'atendida'])->assertOk()->assertJsonPath('data.estado', 'atendida');

        $this->getJson("/api/citas/{$id}")
            ->assertOk()
            ->assertJsonCount(3, 'data.historial')
            ->assertJsonPath('data.historial.2.estado_anterior', 'confirmada')
            ->assertJsonPath('data.historial.2.estado_nuevo', 'atendida')
            ->assertJsonPath('data.transiciones', []);
    }

    public function test_una_cita_cancelada_no_se_puede_confirmar(): void
    {
        $id = $this->crear('10:00', '10:30')->json('data.id');
        $this->patchJson("/api/citas/{$id}/estado", ['estado' => 'cancelada', 'motivo' => 'No asistirá'])->assertOk();

        $this->patchJson("/api/citas/{$id}/estado", ['estado' => 'confirmada'])
            ->assertStatus(409)
            ->assertJsonPath('code', 'TRANSICION_INVALIDA')
            ->assertJsonPath('estado_actual', 'cancelada');
    }

    public function test_una_cita_pendiente_no_pasa_directo_a_atendida(): void
    {
        $id = $this->crear('10:00', '10:30')->json('data.id');

        $this->patchJson("/api/citas/{$id}/estado", ['estado' => 'atendida'])
            ->assertStatus(409)
            ->assertJsonPath('permitidos', ['confirmada', 'cancelada']);
    }

    public function test_cancelar_exige_motivo(): void
    {
        $id = $this->crear('10:00', '10:30')->json('data.id');

        $this->patchJson("/api/citas/{$id}/estado", ['estado' => 'cancelada'])
            ->assertStatus(400)
            ->assertJsonValidationErrors(['motivo']);
    }

    public function test_la_cancelacion_conserva_el_registro_y_el_motivo(): void
    {
        $id = $this->crear('10:00', '10:30')->json('data.id');
        $this->patchJson("/api/citas/{$id}/estado", ['estado' => 'cancelada', 'motivo' => 'Viaje del paciente'])->assertOk();

        $this->assertDatabaseHas('citas', ['id' => $id, 'estado' => 'cancelada']);
        $this->assertDatabaseHas('historial_estados_cita', ['cita_id' => $id, 'estado_nuevo' => 'cancelada', 'motivo' => 'Viaje del paciente']);
    }

    public function test_una_cita_cancelada_no_se_puede_reprogramar(): void
    {
        $id = $this->crear('10:00', '10:30')->json('data.id');
        $this->patchJson("/api/citas/{$id}/estado", ['estado' => 'cancelada', 'motivo' => 'No asistirá'])->assertOk();

        $this->putJson("/api/citas/{$id}", ['fecha' => $this->fecha, 'hora_inicio' => '12:00', 'hora_fin' => '12:30'])
            ->assertStatus(409)
            ->assertJsonPath('code', 'CITA_NO_REPROGRAMABLE');
    }

    public function test_el_detalle_indica_las_acciones_permitidas(): void
    {
        $cita = Cita::factory()->for($this->doctor)->create(['estado' => 'confirmada']);

        $this->getJson("/api/citas/{$cita->id}")
            ->assertJsonPath('data.transiciones', ['atendida', 'cancelada'])
            ->assertJsonPath('data.reprogramable', true);
    }
}
