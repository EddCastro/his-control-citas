<?php

namespace App\Http\Requests\Api;

use Illuminate\Support\Carbon;
use Illuminate\Validation\Validator;

/**
 * Regla compartida: no se agenda ni se reprograma hacia una hora que ya pasó.
 */
class ValidarHorarioFuturo
{
    public function __invoke(Validator $validator): void
    {
        if ($validator->errors()->hasAny(['fecha', 'hora_inicio'])) {
            return;
        }

        $datos = $validator->getData();
        $inicio = Carbon::parse("{$datos['fecha']} {$datos['hora_inicio']}");

        if ($inicio->isPast()) {
            $validator->errors()->add('fecha', 'La cita debe programarse en una fecha y hora futuras.');
        }
    }
}
