# Declaración de uso de inteligencia artificial — Serie II

| Dato | Información |
|---|---|
| Estudiante | Eddy Adolfo Castro Véliz |
| Código | 1890-23-16857 |
| Actividad | Serie II · Módulo funcional: Control de Citas Médicas |

## Herramienta utilizada

Claude, de Anthropic, como apoyo al diseño, a la programación, a las pruebas y
a la redacción de la documentación.

## Propósito del uso

- Traducir el backlog (RQF-01 a RQF-10, RQNF-01 a RQNF-08) a ramas, commits y
  pruebas trazables.
- Diseñar la separación por capas: calendario, cliente de API, controladores,
  servicio de negocio y repositorio.
- Implementar la validación de doble reserva en el servidor con bloqueo por
  doctor dentro de una transacción.
- Escribir las pruebas automatizadas y el script que genera la evidencia.

## Contenido aceptado

- La máquina de estados de la cita y el historial de cambios.
- El bloqueo `SELECT … FOR UPDATE` sobre el doctor para impedir la doble
  reserva bajo concurrencia.
- El uso de 400 para datos inválidos y 409 para conflictos, según RQNF-03.
- Incluir FullCalendar en `public/vendor` para no depender de un CDN.

## Contenido modificado o rechazado

- Se descartó validar el conflicto solo en el calendario: RQNF-07 exige que lo
  decida el servidor; el calendario solo muestra el resultado y revierte el
  movimiento.
- Se descartó eliminar citas canceladas: se cambia su estado y se registra el
  motivo (RQF-05).
- Se reemplazó el 422 que Laravel usa por defecto para validación por 400, como
  pide RQNF-03.

## Errores detectados y corregidos

| Hallazgo | Corrección |
|---|---|
| Al seleccionar un horario, el evento temporal de FullCalendar no tenía estado y rompía el formulario de creación. | Se ignoran los eventos sin estado al preparar la etiqueta accesible. |
| Dentro de Docker, las pruebas podían borrar los datos de la base del entorno. | `phpunit.xml` fuerza la base MySQL `his_citas_test`, creada por `docker/mysql/init`. |
| El mensaje de cancelación sin motivo era poco claro. | Mensaje propio: "Indique el motivo de la cancelación." |

## Validación humana

El estudiante levantó el entorno con `docker compose up`, ejecutó las pruebas,
recorrió el calendario creando, reprogramando y cancelando citas, generó la
evidencia con `scripts/evidencia.ps1` y revisó cada pull request antes de
fusionarlo.

## Responsabilidad académica

La inteligencia artificial se empleó como herramienta de apoyo. Las decisiones,
la validación y la defensa corresponden al estudiante.
