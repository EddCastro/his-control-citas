<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validación de entrada al crear una cita (RQF-01, RQF-08).
 */
class StoreCitaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'paciente_id' => ['required', 'integer', 'exists:pacientes,id'],
            'doctor_id' => ['required', 'integer', Rule::exists('doctores', 'id')->where('activo', true)],
            'fecha' => ['required', 'date_format:Y-m-d'],
            'hora_inicio' => ['required', 'date_format:H:i'],
            'hora_fin' => ['required', 'date_format:H:i', 'after:hora_inicio'],
            'motivo' => ['required', 'string', 'min:3', 'max:255'],
        ];
    }

    public function after(): array
    {
        return [new ValidarHorarioFuturo];
    }

    public function attributes(): array
    {
        return [
            'paciente_id' => 'paciente',
            'doctor_id' => 'doctor',
            'hora_inicio' => 'hora de inicio',
            'hora_fin' => 'hora de fin',
        ];
    }
}
