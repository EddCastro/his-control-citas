<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Doctor extends Model
{
    use HasFactory;

    protected $table = 'doctores';

    protected $fillable = [
        'nombres', 'apellidos', 'especialidad', 'colegiado', 'activo',
    ];

    protected function casts(): array
    {
        return ['activo' => 'boolean'];
    }

    public function citas(): HasMany
    {
        return $this->hasMany(Cita::class);
    }

    public function nombreCompleto(): string
    {
        return "Dr(a). {$this->nombres} {$this->apellidos}";
    }
}
