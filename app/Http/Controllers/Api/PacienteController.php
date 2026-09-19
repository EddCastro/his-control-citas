<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PacienteResource;
use App\Models\Paciente;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/** Lectura de pacientes (RQF-07). */
class PacienteController extends Controller
{
    /** GET /api/pacientes?buscar=texto */
    public function index(Request $request): AnonymousResourceCollection
    {
        $consulta = Paciente::query()->orderBy('apellidos')->orderBy('nombres');

        if ($texto = trim((string) $request->query('buscar'))) {
            $consulta->where(function ($q) use ($texto) {
                $q->where('nombres', 'like', "%{$texto}%")
                    ->orWhere('apellidos', 'like', "%{$texto}%");
            });
        }

        return PacienteResource::collection($consulta->get());
    }

    /** GET /api/pacientes/{id} */
    public function show(int $id): PacienteResource
    {
        return PacienteResource::make(Paciente::query()->findOrFail($id));
    }
}
