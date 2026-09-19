<?php

namespace App\Providers;

use App\Repositories\CitaRepository;
use App\Repositories\EloquentCitaRepository;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // La lógica de negocio depende del contrato; aquí se elige la implementación.
        $this->app->bind(CitaRepository::class, EloquentCitaRepository::class);
    }

    public function boot(): void
    {
        //
    }
}
