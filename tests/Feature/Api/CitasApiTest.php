<?php

namespace Tests\Feature\Api;

use App\Models\Cita;
use App\Models\Doctor;
use App\Models\Paciente;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CitasApiTest extends TestCase
{
    use RefreshDatabase;

    private Doctor $doctor;

    private Paciente $paciente;

    private string $manana;

    protected function setUp(): void
    {
        parent::setUp();
        $this->doctor = Doctor::factory()->create();
        $this->paciente = Paciente::factory()->create();
        $this->manana = now()->addDay()->format('Y-m-d');
    }

    private function datos(array $cambios = []): array
    {
        return array_merge([
            'paciente_id' => $this->paciente->id,
            'doctor_id' => $this->doctor->id,
            'fecha' => $this->manana,
            'hora_inicio' => '10:00',
            'hora_fin' => '10:30',
            'motivo' => 'Control general',
        ], $cambios);
    }

    public function test_crea_una_cita_pendiente_rqf_01(): void
    {
        $this->postJson('/api/citas', $this->datos())
            ->assertCreated()
            ->assertJsonPath('data.estado', 'pendiente')
            ->assertJsonPath('data.hora_inicio', '10:00')
            ->assertJsonPath('data.doctor.id', $this->doctor->id);

        $this->assertDatabaseCount('citas', 1);
    }

    public function test_rechaza_campos_obligatorios_faltantes_con_400_rqf_08(): void
    {
        $this->postJson('/api/citas', [])
            ->assertStatus(400)
            ->assertJsonPath('code', 'DATOS_INVALIDOS')
            ->assertJsonValidationErrors(['paciente_id', 'doctor_id', 'fecha', 'hora_inicio', 'hora_fin', 'motivo']);
    }

    public function test_rechaza_formato_de_fecha_y_hora_invalido_rqf_08(): void
    {
        $this->postJson('/api/citas', $this->datos(['fecha' => '01/10/2026', 'hora_inicio' => '10am']))
            ->assertStatus(400)
            ->assertJsonValidationErrors(['fecha', 'hora_inicio']);
    }

    public function test_rechaza_hora_fin_anterior_a_inicio_rqf_08(): void
    {
        $this->postJson('/api/citas', $this->datos(['hora_inicio' => '11:00', 'hora_fin' => '10:00']))
            ->assertStatus(400)
            ->assertJsonValidationErrors(['hora_fin']);
    }

    public function test_rechaza_paciente_o_doctor_inexistente_rqf_08(): void
    {
        $this->postJson('/api/citas', $this->datos(['paciente_id' => 999, 'doctor_id' => 999]))
            ->assertStatus(400)
            ->assertJsonValidationErrors(['paciente_id', 'doctor_id']);
    }

    public function test_rechaza_una_fecha_pasada(): void
    {
        $this->postJson('/api/citas', $this->datos(['fecha' => now()->subDay()->format('Y-m-d')]))
            ->assertStatus(400)
            ->assertJsonValidationErrors(['fecha']);
    }

    public function test_devuelve_el_detalle_de_una_cita_rqf_09(): void
    {
        $cita = Cita::factory()->create();

        $this->getJson("/api/citas/{$cita->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $cita->id)
            ->assertJsonStructure(['data' => ['paciente' => ['nombre'], 'doctor' => ['nombre', 'especialidad'], 'motivo', 'estado', 'inicio', 'fin']]);
    }

    public function test_cita_inexistente_responde_404(): void
    {
        $this->getJson('/api/citas/9999')->assertNotFound()->assertJsonPath('code', 'NO_ENCONTRADO');
        $this->putJson('/api/citas/9999', ['fecha' => $this->manana, 'hora_inicio' => '09:00', 'hora_fin' => '09:30'])->assertNotFound();
    }

    public function test_lista_y_filtra_por_doctor_y_rango_de_fechas_rqf_06(): void
    {
        $otroDoctor = Doctor::factory()->create();
        $base = now()->addDays(3)->setTime(9, 0);

        Cita::factory()->for($this->doctor)->create(['inicio' => $base, 'fin' => $base->copy()->addMinutes(30)]);
        Cita::factory()->for($otroDoctor)->create(['inicio' => $base, 'fin' => $base->copy()->addMinutes(30)]);
        Cita::factory()->for($this->doctor)->create(['inicio' => $base->copy()->addDays(20), 'fin' => $base->copy()->addDays(20)->addMinutes(30)]);

        $this->getJson('/api/citas')->assertOk()->assertJsonCount(3, 'data');

        $this->getJson("/api/citas?doctor_id={$this->doctor->id}")->assertOk()->assertJsonCount(2, 'data');

        $desde = $base->format('Y-m-d');
        $hasta = $base->copy()->addDays(5)->format('Y-m-d');
        $this->getJson("/api/citas?doctor_id={$this->doctor->id}&desde={$desde}&hasta={$hasta}")
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_filtro_con_rango_invertido_responde_400(): void
    {
        $this->getJson('/api/citas?desde=2026-10-10&hasta=2026-10-01')->assertStatus(400)->assertJsonValidationErrors(['hasta']);
    }

    public function test_reprograma_una_cita_rqf_04(): void
    {
        $cita = Cita::factory()->for($this->doctor)->create();
        $nueva = now()->addDays(4)->format('Y-m-d');

        $this->putJson("/api/citas/{$cita->id}", ['fecha' => $nueva, 'hora_inicio' => '15:00', 'hora_fin' => '15:45'])
            ->assertOk()
            ->assertJsonPath('data.fecha', $nueva)
            ->assertJsonPath('data.hora_inicio', '15:00')
            ->assertJsonPath('data.hora_fin', '15:45');

        $this->assertDatabaseHas('citas', ['id' => $cita->id, 'inicio' => "{$nueva} 15:00:00"]);
    }

    public function test_cancela_sin_eliminar_el_registro_rqf_05(): void
    {
        $cita = Cita::factory()->create();

        $this->patchJson("/api/citas/{$cita->id}/estado", ['estado' => 'cancelada', 'motivo' => 'El paciente no puede asistir'])
            ->assertOk()
            ->assertJsonPath('data.estado', 'cancelada');

        $this->assertDatabaseHas('citas', ['id' => $cita->id, 'estado' => 'cancelada']);
    }

    public function test_estado_invalido_responde_400(): void
    {
        $cita = Cita::factory()->create();

        $this->patchJson("/api/citas/{$cita->id}/estado", ['estado' => 'borrada'])->assertStatus(400);
    }

    public function test_lista_doctores_y_pacientes_rqf_07(): void
    {
        $this->getJson('/api/doctores')->assertOk()->assertJsonCount(1, 'data')->assertJsonStructure(['data' => [['id', 'nombre', 'especialidad']]]);
        $this->getJson('/api/pacientes')->assertOk()->assertJsonCount(1, 'data')->assertJsonStructure(['data' => [['id', 'nombre']]]);
    }

    public function test_ruta_inexistente_responde_json_404(): void
    {
        $this->getJson('/api/no-existe')->assertNotFound()->assertJsonPath('code', 'NO_ENCONTRADO');
    }
}
