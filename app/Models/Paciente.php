<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Paciente extends Model
{
    use HasFactory;

    protected $table = 'pacientes';

    protected $fillable = [
        'nombres', 'apellidos', 'dpi', 'fecha_nacimiento', 'telefono', 'correo',
    ];

    protected function casts(): array
    {
        return ['fecha_nacimiento' => 'date:Y-m-d'];
    }

    public function citas(): HasMany
    {
        return $this->hasMany(Cita::class);
    }

    public function nombreCompleto(): string
    {
        return "{$this->nombres} {$this->apellidos}";
    }
}
