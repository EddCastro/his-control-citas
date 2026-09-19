<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\DoctorResource;
use App\Models\Doctor;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/** Lectura de doctores (RQF-07). */
class DoctorController extends Controller
{
    /** GET /api/doctores */
    public function index(): AnonymousResourceCollection
    {
        $doctores = Doctor::query()->where('activo', true)->orderBy('apellidos')->get();

        return DoctorResource::collection($doctores);
    }

    /** GET /api/doctores/{id} */
    public function show(int $id): DoctorResource
    {
        return DoctorResource::make(Doctor::query()->findOrFail($id));
    }
}
