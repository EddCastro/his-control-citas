<?php

namespace App\Http\Requests\Api;

use App\Domain\Citas\EstadoCita;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Filtros de GET /api/citas (RQF-06). */
class ListarCitasRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'doctor_id' => ['nullable', 'integer', 'exists:doctores,id'],
            'paciente_id' => ['nullable', 'integer', 'exists:pacientes,id'],
            'estado' => ['nullable', Rule::in(EstadoCita::valores())],
            'desde' => ['nullable', 'date'],
            'hasta' => ['nullable', 'date', 'after_or_equal:desde'],
        ];
    }

    public function filtros(): array
    {
        return $this->only(['doctor_id', 'paciente_id', 'estado', 'desde', 'hasta']);
    }
}
