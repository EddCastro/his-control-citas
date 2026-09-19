<?php

namespace App\Http\Requests\Api;

use App\Domain\Citas\EstadoCita;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Cambio de estado de una cita (RQF-05). */
class CambiarEstadoCitaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'estado' => ['required', Rule::in(EstadoCita::valores())],
        ];
    }

    public function estado(): EstadoCita
    {
        return EstadoCita::from($this->validated('estado'));
    }
}
