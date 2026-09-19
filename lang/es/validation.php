<?php

// Mensajes de validación en español para las reglas que usa la API (RQF-08).
return [
    'after' => 'El campo :attribute debe ser posterior a :date.',
    'after_or_equal' => 'El campo :attribute debe ser igual o posterior a :date.',
    'date' => 'El campo :attribute no es una fecha válida.',
    'date_format' => 'El campo :attribute debe tener el formato :format.',
    'exists' => 'El :attribute seleccionado no existe.',
    'in' => 'El valor de :attribute no es válido. Valores permitidos: :values.',
    'integer' => 'El campo :attribute debe ser un número entero.',
    'max' => [
        'string' => 'El campo :attribute no debe superar :max caracteres.',
    ],
    'min' => [
        'string' => 'El campo :attribute debe tener al menos :min caracteres.',
    ],
    'required' => 'El campo :attribute es obligatorio.',
    'required_if' => 'El campo :attribute es obligatorio cuando :other es :value.',
    'string' => 'El campo :attribute debe ser texto.',

    'attributes' => [
        'paciente_id' => 'paciente',
        'doctor_id' => 'doctor',
        'fecha' => 'fecha',
        'hora_inicio' => 'hora de inicio',
        'hora_fin' => 'hora de fin',
        'motivo' => 'motivo',
        'estado' => 'estado',
        'desde' => 'desde',
        'hasta' => 'hasta',
    ],
];
