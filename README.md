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

## Datos

Todos los nombres, DPI y teléfonos son ficticios.
