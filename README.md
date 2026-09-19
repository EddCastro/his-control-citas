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
| `historial_estados_cita` | Cada cambio de estado con su motivo |

Esquema: `database/migrations/`. Datos semilla: `database/seeders/`.

Los datos persisten en el volumen `mysql_data` aunque se detengan o eliminen
los contenedores. Para empezar de cero:

```powershell
docker compose down -v
docker compose up -d
```

## Calendario (FullCalendar)

Abrir **http://localhost:8000** después de `docker compose up -d`.

| Acción | Cómo | Requisito |
|---|---|---|
| Ver citas | Vistas Mes, Semana, Día y Lista | RQF-02 |
| Crear cita | Seleccionar un horario vacío o pulsar **+ Nueva cita** | RQF-01 |
| Ver detalle | Clic sobre la cita: datos, estado e historial | RQF-09 |
| Reprogramar | Arrastrar la cita (o estirar su borde); se guarda con `PUT /api/citas/{id}` | RQF-04 |
| Confirmar, atender o cancelar | Botones del detalle; cancelar pide motivo | RQF-05 |
| Filtrar | Doctor en el panel izquierdo; listado por doctor y rango de fechas | RQF-06 |
| Colores por estado | Pendiente ámbar, confirmada azul, atendida verde, cancelada gris tachada | RQF-10 |

Si el servidor rechaza un movimiento (por ejemplo, 409 por conflicto de
horario), la cita vuelve a su lugar y se muestra el motivo. El calendario no
decide si un horario está libre: lo decide la API (RQNF-07). Los botones de
estado se generan con las `transiciones` que devuelve la API.

La interfaz se adapta a escritorio y tableta (RQNF-06): por debajo de 1024 px
el panel lateral pasa arriba del calendario.

FullCalendar 6.1.21 se incluye en `public/vendor/` (licencia MIT), así que la
página funciona sin depender de un CDN.

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
| 409 | El doctor ya tiene una cita activa que se cruza con el horario | `CONFLICTO_HORARIO` |
| 409 | El cambio de estado no está permitido desde el estado actual | `TRANSICION_INVALIDA` |
| 409 | Se intenta mover una cita cancelada o atendida | `CITA_NO_REPROGRAMABLE` |

Respuesta de conflicto de horario (RQF-03):

```json
{
  "message": "El doctor ya tiene una cita pendiente de 10:00 a 10:30 el 05/10/2026.",
  "code": "CONFLICTO_HORARIO",
  "cita_en_conflicto": { "id": 12, "inicio": "2026-10-05T10:00:00", "fin": "2026-10-05T10:30:00", "estado": "pendiente" }
}
```

## Estados de la cita (RQF-05)

```
pendiente ──► confirmada ──► atendida
    │              │
    └──► cancelada ◄┘
```

| Regla | Detalle |
|---|---|
| Estados que ocupan horario | `pendiente` y `confirmada` |
| Estados finales | `cancelada` y `atendida`: no cambian ni se reprograman |
| Cancelar | Exige `motivo`; la cita **no se elimina** |
| Historial | Cada cambio queda en `historial_estados_cita` con estado anterior, nuevo, motivo y fecha |

Cuerpo de `PATCH /citas/{id}/estado`:

```json
{ "estado": "cancelada", "motivo": "El paciente no puede asistir" }
```

## Validación de disponibilidad en el servidor (RQNF-07)

`CitaService` valida el horario dentro de una transacción:

1. Bloquea la fila del doctor con `SELECT … FOR UPDATE`.
2. Busca una cita activa del mismo doctor con `inicio < fin_nuevo` y `fin > inicio_nuevo`.
3. Si existe, responde 409; si no, guarda la cita.

Una segunda solicitud simultánea para el mismo doctor espera en el paso 1 y, al
continuar, ya ve la cita recién creada. Dos citas contiguas (10:00–10:30 y
10:30–11:00) no se consideran cruce. Confirmar y cancelar leen la cita
bloqueada, de modo que dos usuarios no pueden aplicar transiciones
incompatibles al mismo tiempo.

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
| Presentación (calendario) | `resources/views/calendario.blade.php`, `public/js/calendario.js`, `public/js/mapeo-eventos.js` | Mostrar citas y capturar acciones del usuario. Sin reglas de negocio |
| Cliente de la API | `public/js/api-citas.js` | Única pieza del frontend que conoce las rutas HTTP |
| API (presentación HTTP) | `routes/api.php`, `app/Http/Controllers/Api`, `app/Http/Requests/Api`, `app/Http/Resources` | Validar la entrada, delegar y dar formato a la respuesta. Sin reglas de negocio |
| Lógica de negocio | `app/Services`, `app/Domain` | Reglas de la cita: disponibilidad, transiciones de estado, historial |
| Acceso a datos | `app/Repositories`, `app/Models` | Consultas y persistencia. `CitaRepository` es el contrato; `EloquentCitaRepository`, la implementación |

## Pruebas

```powershell
docker compose exec app php artisan test
```

Las pruebas corren sobre MySQL del contenedor, en la base `his_citas_test`,
que `docker/mysql/init` crea al iniciar el volumen. Así no borran los datos de
`his_citas`. Si el volumen se creó antes de ese script, recréelo una vez con
`docker compose down -v` y `docker compose up -d`.

## Flujo Git

Cada rama sale de `main` con el último merge, se integra con un pull request
(merge commit, sin squash) y cada PR cambia menos de 400 líneas.

| PR | Rama | Contenido | Requisitos |
|---|---|---|---|
| #1 | `feature/docker-mysql-schema` | MySQL 8.4 en Docker con volumen, tablas y modelos | RQNF-01, RQF-01, RQF-06 |
| #2 | `feature/seeders-contenedor-app` | Datos semilla y contenedor Laravel que migra al iniciar | RQNF-02, RQF-02 |
| #3 | `feature/servicio-validacion-citas` | Repositorio, servicio y validación de entrada | RQF-08, RQNF-04 |
| #4 | `feature/api-rest-citas` | Endpoints REST y errores JSON 400/404 | RQF-06, RQF-07, RQF-09, RQNF-03 |
| #5 | `feature/pruebas-api-citas` | Pruebas y documentación de la API | RQF-01, RQF-06, RQF-08 |
| #6 | `feature/validacion-conflictos-estados` | Transiciones, historial y bloqueo de agenda en servidor | RQF-03, RQF-05, RQNF-07 |
| #7 | `feature/respuestas-409-pruebas` | Respuestas 409 y pruebas de conflictos y estados | RQF-03, RQF-05, RQNF-03 |
| #8 | `feature/fullcalendar-ui` | Página del calendario con FullCalendar y diseño adaptable | RQF-02, RQF-10, RQNF-06 |
| #9 | `feature/cliente-api-eventos` | Cliente de la API y mapeo de citas a eventos por color | RQF-10, RQNF-04 |
| #10 | `feature/fullcalendar-interacciones` | Crear, ver detalle y arrastrar para reprogramar | RQF-01, RQF-04, RQF-09 |
| #11 | `feature/evidencia` | Capturas, script de evidencia y declaración de uso de IA | RQNF-08 |
| #12 | `feature/evidencia-resultados` | `EVIDENCIA.md` con las salidas reales del entorno | RQNF-05, RQNF-08 |

## Evidencia

[`EVIDENCIA.md`](EVIDENCIA.md) reúne capturas, comandos, respuestas de la API,
`docker ps` y `git log --graph`. Se genera con:

```powershell
powershell -ExecutionPolicy Bypass -File .\scripts\evidencia.ps1
```

Las capturas del calendario están en [`docs/evidencia/capturas`](docs/evidencia/capturas).

## Datos

Todos los nombres, DPI y teléfonos son ficticios.
