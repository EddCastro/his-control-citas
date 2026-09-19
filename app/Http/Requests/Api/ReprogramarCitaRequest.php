<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Nueva fecha y hora para una cita existente (RQF-04, RQF-08).
 * Es la petición que envía el calendario al arrastrar un evento.
 */
class ReprogramarCitaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'fecha' => ['required', 'date_format:Y-m-d'],
            'hora_inicio' => ['required', 'date_format:H:i'],
            'hora_fin' => ['required', 'date_format:H:i', 'after:hora_inicio'],
        ];
    }

    public function after(): array
    {
        return [new ValidarHorarioFuturo];
    }

    public function attributes(): array
    {
        return ['hora_inicio' => 'hora de inicio', 'hora_fin' => 'hora de fin'];
    }
}
