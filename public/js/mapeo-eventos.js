/**
 * Traducción entre citas de la API y eventos de FullCalendar.
 * Sin efectos: solo transforma datos.
 */

/** Colores por estado (RQF-10). El texto del estado también se muestra, no solo el color. */
export const ESTADOS = {
    pendiente: { etiqueta: 'Pendiente', fondo: '#f59e0b', borde: '#b45309', texto: '#1f1300' },
    confirmada: { etiqueta: 'Confirmada', fondo: '#2563eb', borde: '#1e40af', texto: '#ffffff' },
    atendida: { etiqueta: 'Atendida', fondo: '#15803d', borde: '#14532d', texto: '#ffffff' },
    cancelada: { etiqueta: 'Cancelada', fondo: '#e5e7eb', borde: '#6b7280', texto: '#374151' },
};

export const ACCIONES = {
    confirmada: 'Confirmar cita',
    atendida: 'Marcar como atendida',
    cancelada: 'Cancelar cita',
};

/** Convierte una cita de la API en un evento del calendario. */
export function citaAEvento(cita) {
    const estilo = ESTADOS[cita.estado] ?? ESTADOS.pendiente;
    return {
        id: String(cita.id),
        title: `${cita.paciente.nombre} · ${cita.motivo}`,
        start: cita.inicio,
        end: cita.fin,
        backgroundColor: estilo.fondo,
        borderColor: estilo.borde,
        textColor: estilo.texto,
        classNames: ['cita', `cita--${cita.estado}`],
        // Solo se arrastran las citas que el servidor declara reprogramables.
        editable: Boolean(cita.reprogramable),
        extendedProps: {
            estado: cita.estado,
            paciente: cita.paciente.nombre,
            doctor: cita.doctor.nombre,
            especialidad: cita.doctor.especialidad,
            motivo: cita.motivo,
        },
    };
}

const dos = (n) => String(n).padStart(2, '0');

/** Fecha local en formato Y-m-d. */
export function fechaISO(fecha) {
    return `${fecha.getFullYear()}-${dos(fecha.getMonth() + 1)}-${dos(fecha.getDate())}`;
}

/** Hora local en formato H:i. */
export function horaISO(fecha) {
    return `${dos(fecha.getHours())}:${dos(fecha.getMinutes())}`;
}

/** Cuerpo de PUT /api/citas/{id} a partir de un evento movido (RQF-04). */
export function eventoAHorario(evento, duracionOriginalMs) {
    const inicio = evento.start;
    const fin = evento.end ?? new Date(inicio.getTime() + duracionOriginalMs);
    return { fecha: fechaISO(inicio), hora_inicio: horaISO(inicio), hora_fin: horaISO(fin) };
}

/** "lunes 5 de octubre de 2026" */
export function fechaLarga(isoFecha) {
    const [a, m, d] = isoFecha.split('-').map(Number);
    return new Date(a, m - 1, d).toLocaleDateString('es-GT', {
        weekday: 'long', day: 'numeric', month: 'long', year: 'numeric',
    });
}
