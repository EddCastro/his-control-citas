# HIS — Control de Citas Médicas

Serie II · Módulo funcional del Sistema Hospitalario Integrado (HIS): calendario
interactivo con FullCalendar para agendar, reprogramar y cancelar citas médicas,
respaldado por una API REST en Laravel 12 y una base de datos MySQL en Docker.

| Dato | Información |
|---|---|
| Curso | Análisis de Sistemas II |
| Estudiante | Eddy Adolfo Castro Véliz |
| Código | 1890-23-16857 |
| GitHub | EddCastro |

El desarrollo se integra por ramas `feature/*` hacia `main` mediante pull request.

## Requisitos

- Docker Desktop (Docker Compose v2).
- Git.

MySQL se ejecuta **solo en Docker** (RQNF-01); no se necesita instalar MySQL ni
PHP en el equipo.

## Levantar el entorno

```powershell
git clone https://github.com/EddCastro/his-control-citas.git
cd his-control-citas
docker compose up -d --build
```

Un solo comando levanta los dos contenedores (RQNF-02):

| Contenedor | Servicio | Puerto en el equipo |
|---|---|---|
| `his-mysql` | MySQL 8.4 con volumen persistente `mysql_data` | 3307 |
| `his-app` | Laravel 12 (PHP 8.3) | 8000 |

Al iniciar, `his-app` instala dependencias la primera vez, aplica las
migraciones y ejecuta los seeders. Los seeders se pueden repetir sin duplicar
registros.

La primera ejecución tarda unos minutos mientras se descargan las imágenes y
las dependencias. Para seguir el avance:

```powershell
docker compose logs -f app
```

## Base de datos

| Tabla | Contenido |
|---|---|
| `pacientes` | Pacientes registrados (8 de ejemplo) |
| `doctores` | Doctores con especialidad y colegiado (4 de ejemplo) |
| `citas` | Citas con paciente, doctor, inicio, fin, motivo y estado (11 de ejemplo) |

Esquema: `database/migrations/`. Datos semilla: `database/seeders/`.

Los datos persisten en el volumen `mysql_data` aunque se detengan o eliminen
los contenedores. Para empezar de cero:

```powershell
docker compose down -v
docker compose up -d
```

## API REST

Base: `http://localhost:8000/api`. Todas las respuestas son JSON (RQNF-03).

| Método | Ruta | Propósito | Requisitos |
|---|---|---|---|
| GET | `/citas` | Lista citas. Filtros: `doctor_id`, `paciente_id`, `estado`, `desde`, `hasta` | RQF-02, RQF-06 |
| POST | `/citas` | Crea una cita en estado pendiente | RQF-01, RQF-08 |
| GET | `/citas/{id}` | Detalle de una cita | RQF-09 |
| PUT | `/citas/{id}` | Reprograma fecha y hora | RQF-04 |
| PATCH | `/citas/{id}/estado` | Cambia el estado | RQF-05 |
| GET | `/doctores` | Doctores activos | RQF-07 |
| GET | `/pacientes` | Pacientes (`?buscar=` por nombre) | RQF-07 |

Cuerpo de `POST /citas`:

```json
{
  "paciente_id": 1,
  "doctor_id": 1,
  "fecha": "2026-10-05",
  "hora_inicio": "10:00",
  "hora_fin": "10:30",
  "motivo": "Control de presión arterial"
}
```

### Códigos HTTP

| Código | Cuándo | `code` |
|---|---|---|
| 200 | Consulta, reprogramación o cambio de estado correctos | — |
| 201 | Cita creada | — |
| 400 | Campo obligatorio ausente, formato de fecha u hora inválido, paciente o doctor inexistente, fecha pasada | `DATOS_INVALIDOS` |
| 404 | La cita, el doctor o el paciente no existen | `NO_ENCONTRADO` |

Formato de error:

```json
{
  "message": "Los datos enviados no son válidos.",
  "code": "DATOS_INVALIDOS",
  "errors": { "hora_fin": ["El campo hora de fin debe ser posterior a hora de inicio."] }
}
```

## Arquitectura por capas (RQNF-04)

| Capa | Ubicación | Responsabilidad |
|---|---|---|
| API (presentación HTTP) | `routes/api.php`, `app/Http/Controllers/Api`, `app/Http/Requests/Api`, `app/Http/Resources` | Validar la entrada, delegar y dar formato a la respuesta. Sin reglas de negocio |
| Lógica de negocio | `app/Services`, `app/Domain` | Reglas de la cita: intervalo, estados |
| Acceso a datos | `app/Repositories`, `app/Models` | Consultas y persistencia. `CitaRepository` es el contrato; `EloquentCitaRepository`, la implementación |

## Pruebas

```powershell
docker compose exec app php artisan test
```

Las pruebas corren sobre MySQL del contenedor, en la base `his_citas_test`,
que `docker/mysql/init` crea al iniciar el volumen. Así no borran los datos de
`his_citas`. Si el volumen se creó antes de ese script, recréelo una vez con
`docker compose down -v` y `docker compose up -d`.

## Datos

Todos los nombres, DPI y teléfonos son ficticios.
