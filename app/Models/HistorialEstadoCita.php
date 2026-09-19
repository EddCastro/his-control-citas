<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HistorialEstadoCita extends Model
{
    protected $table = 'historial_estados_cita';

    public const UPDATED_AT = null;

    protected $fillable = ['cita_id', 'estado_anterior', 'estado_nuevo', 'motivo'];

    public function cita(): BelongsTo
    {
        return $this->belongsTo(Cita::class);
    }
}
