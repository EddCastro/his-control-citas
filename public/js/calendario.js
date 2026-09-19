/**
 * Capa de presentación: calendario interactivo (RQF-02, RQF-04, RQF-09, RQF-10).
 *
 * Los manejadores de eventos del calendario no contienen reglas de negocio:
 * toda validación (conflictos, transiciones, formatos) la decide el servidor y
 * aquí solo se muestra el resultado (RQNF-04, RQNF-07).
 */
import { ApiCitas } from './api-citas.js';
import { ACCIONES, ESTADOS, citaAEvento, eventoAHorario, fechaISO, fechaLarga, horaISO } from './mapeo-eventos.js';

const api = new ApiCitas();
const $ = (sel) => document.querySelector(sel);
const esc = (t) => String(t ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

const estado = { doctorId: '', mostrarCanceladas: true, citaAbierta: null };

// ---------------------------------------------------------------- avisos
function avisar(mensaje, tipo = 'info') {
    const aviso = document.createElement('div');
    aviso.className = `aviso aviso--${tipo}`;
    aviso.setAttribute('role', tipo === 'error' ? 'alert' : 'status');
    aviso.textContent = mensaje;
    $('#avisos').append(aviso);
    setTimeout(() => aviso.remove(), tipo === 'error' ? 8000 : 4000);
}

function mensajeDeError(respuesta) {
    const d = respuesta.data ?? {};
    if (respuesta.status === 400 && d.errors) {
        return Object.values(d.errors).flat().join(' ');
    }
    return d.message ?? `Error ${respuesta.status}`;
}

// ---------------------------------------------------------------- catálogos
async function cargarCatalogos() {
    const [doctores, pacientes] = await Promise.all([api.listarDoctores(), api.listarPacientes()]);

    if (!doctores.ok || !pacientes.ok) {
        avisar('No se pudieron cargar doctores y pacientes.', 'error');
        return;
    }

    const opcionesDoctor = doctores.data.data
        .map((d) => `<option value="${d.id}">${esc(d.nombre)} — ${esc(d.especialidad)}</option>`)
        .join('');
    $('#filtro-doctor').innerHTML = `<option value="">Todos los doctores</option>${opcionesDoctor}`;
    $('#listado-doctor').innerHTML = `<option value="">Todos los doctores</option>${opcionesDoctor}`;
    $('#crear-doctor').innerHTML = `<option value="">Seleccione un doctor</option>${opcionesDoctor}`;

    $('#crear-paciente').innerHTML = '<option value="">Seleccione un paciente</option>' + pacientes.data.data
        .map((p) => `<option value="${p.id}">${esc(p.nombre)} (DPI ${esc(p.dpi)})</option>`)
        .join('');
}

// ---------------------------------------------------------------- calendario
const calendario = new FullCalendar.Calendar($('#calendario'), {
    locale: 'es',
    timeZone: 'local',
    initialView: 'timeGridWeek',
    firstDay: 1,
    height: 'auto',
    nowIndicator: true,
    slotMinTime: '07:00:00',
    slotMaxTime: '19:00:00',
    slotDuration: '00:15:00',
    slotLabelInterval: '01:00',
    allDaySlot: false,
    businessHours: { daysOfWeek: [1, 2, 3, 4, 5, 6], startTime: '07:00', endTime: '19:00' },
    headerToolbar: {
        left: 'prev,next today',
        center: 'title',
        right: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek',
    },
    buttonText: { today: 'Hoy', month: 'Mes', week: 'Semana', day: 'Día', list: 'Lista' },
    selectable: true,
    selectMirror: true,
    editable: true,
    eventDurationEditable: true,
    eventOverlap: true,
    dayMaxEvents: 3,
    eventDisplay: 'block',
    eventTimeFormat: { hour: '2-digit', minute: '2-digit', hour12: false },
    slotLabelFormat: { hour: '2-digit', minute: '2-digit', hour12: false },

    // RQF-02 y RQF-06: el calendario pide al servidor solo el rango visible.
    events: async (info, exito, fallo) => {
        const r = await api.listarCitas({ desde: info.startStr, hasta: info.endStr, doctor_id: estado.doctorId });
        if (!r.ok) {
            avisar(mensajeDeError(r), 'error');
            fallo(new Error('No se pudieron cargar las citas'));
            return;
        }
        const citas = r.data.data.filter((c) => estado.mostrarCanceladas || c.estado !== 'cancelada');
        exito(citas.map(citaAEvento));
        $('#resumen').textContent = `${citas.length} cita${citas.length === 1 ? '' : 's'} en este rango`;
    },

    // RQF-01: seleccionar un horario abre el formulario con fecha y horas.
    select: (info) => {
        if (info.allDay) {
            const inicio = new Date(info.start);
            inicio.setHours(8, 0, 0, 0);
            abrirFormularioCrear(inicio, new Date(inicio.getTime() + 30 * 60000));
        } else {
            abrirFormularioCrear(info.start, info.end);
        }
        calendario.unselect();
    },

    // RQF-09: clic en un evento muestra su detalle.
    eventClick: (info) => {
        info.jsEvent.preventDefault();
        abrirDetalle(info.event.id);
    },

    // En la vista de mes no se permite soltar sobre "todo el día".
    eventAllow: (drop) => !drop.allDay,

    // RQF-04: arrastrar o estirar sincroniza con la base de datos.
    eventDrop: (info) => reprogramar(info),
    eventResize: (info) => reprogramar(info),

    eventDidMount: (info) => {
        const p = info.event.extendedProps;
        if (!p.estado) return; // evento temporal de la selección
        info.el.setAttribute('title', `${ESTADOS[p.estado].etiqueta} · ${p.paciente} · ${p.doctor}\n${p.motivo}`);
        info.el.setAttribute('aria-label', `Cita ${ESTADOS[p.estado].etiqueta.toLowerCase()} de ${p.paciente} con ${p.doctor}`);
    },
});

async function reprogramar(info) {
    const duracion = info.oldEvent.end ? info.oldEvent.end - info.oldEvent.start : 30 * 60000;
    const r = await api.reprogramarCita(info.event.id, eventoAHorario(info.event, duracion));

    if (r.ok) {
        const c = r.data.data;
        avisar(`Cita reprogramada al ${fechaLarga(c.fecha)}, ${c.hora_inicio}–${c.hora_fin}.`, 'exito');
        calendario.refetchEvents();
    } else {
        // El servidor rechazó el cambio (409 conflicto, 400 fecha pasada…): se deshace.
        info.revert();
        avisar(mensajeDeError(r), 'error');
    }
}

// ---------------------------------------------------------------- crear cita
function abrirFormularioCrear(inicio, fin) {
    const form = $('#form-crear');
    form.reset();
    limpiarErrores(form);
    form.fecha.value = fechaISO(inicio);
    form.hora_inicio.value = horaISO(inicio);
    form.hora_fin.value = horaISO(fin);
    if (estado.doctorId) form.doctor_id.value = estado.doctorId;
    $('#dlg-crear').showModal();
    form.paciente_id.focus();
}

function limpiarErrores(form) {
    form.querySelectorAll('.error-campo').forEach((e) => (e.textContent = ''));
    form.querySelectorAll('[aria-invalid]').forEach((e) => e.removeAttribute('aria-invalid'));
    $('#crear-error').hidden = true;
}

$('#form-crear').addEventListener('submit', async (ev) => {
    ev.preventDefault();
    const form = ev.target;
    limpiarErrores(form);

    const datos = Object.fromEntries(new FormData(form));
    datos.paciente_id = Number(datos.paciente_id) || null;
    datos.doctor_id = Number(datos.doctor_id) || null;

    const boton = form.querySelector('[type=submit]');
    boton.disabled = true;
    const r = await api.crearCita(datos);
    boton.disabled = false;

    if (r.ok) {
        $('#dlg-crear').close();
        avisar(`Cita creada: ${r.data.data.paciente.nombre}, ${fechaLarga(r.data.data.fecha)} a las ${r.data.data.hora_inicio}.`, 'exito');
        calendario.refetchEvents();
        return;
    }

    if (r.status === 400 && r.data.errors) {
        Object.entries(r.data.errors).forEach(([campo, mensajes]) => {
            const destino = form.querySelector(`[data-error-de="${campo}"]`);
            const control = form.elements[campo];
            if (destino) destino.textContent = mensajes.join(' ');
            if (control) control.setAttribute('aria-invalid', 'true');
        });
    }

    const caja = $('#crear-error');
    caja.textContent = mensajeDeError(r);
    caja.hidden = false;
});

$('#btn-nueva').addEventListener('click', () => {
    const inicio = new Date();
    inicio.setDate(inicio.getDate() + 1);
    inicio.setHours(8, 0, 0, 0);
    abrirFormularioCrear(inicio, new Date(inicio.getTime() + 30 * 60000));
});

// ---------------------------------------------------------------- detalle
async function abrirDetalle(id) {
    const r = await api.obtenerCita(id);
    if (!r.ok) {
        avisar(mensajeDeError(r), 'error');
        return;
    }
    estado.citaAbierta = r.data.data;
    pintarDetalle(estado.citaAbierta);
    $('#dlg-detalle').showModal();
}

function pintarDetalle(c) {
    const e = ESTADOS[c.estado];
    $('#detalle-titulo').textContent = `Cita #${c.id}`;
    $('#detalle-cuerpo').innerHTML = `
        <p><span class="insignia insignia--${c.estado}">${e.etiqueta}</span></p>
        <dl class="resumen-cita">
            <dt>Paciente</dt><dd>${esc(c.paciente.nombre)}</dd>
            <dt>Doctor</dt><dd>${esc(c.doctor.nombre)} · ${esc(c.doctor.especialidad)}</dd>
            <dt>Fecha</dt><dd>${fechaLarga(c.fecha)}</dd>
            <dt>Horario</dt><dd>${c.hora_inicio} a ${c.hora_fin}</dd>
            <dt>Motivo</dt><dd>${esc(c.motivo)}</dd>
        </dl>
        ${c.reprogramable ? '<p class="ayuda">Para reprogramar, arrastre la cita en el calendario.</p>' : ''}
        <h3>Historial</h3>
        <ol class="historial">
            ${(c.historial ?? []).map((h) => `
                <li><strong>${h.estado_anterior ? ESTADOS[h.estado_anterior].etiqueta + ' → ' : ''}${ESTADOS[h.estado_nuevo].etiqueta}</strong>
                <span class="ayuda">${new Date(h.fecha).toLocaleString('es-GT', { dateStyle: 'medium', timeStyle: 'short' })}${h.motivo ? ' · ' + esc(h.motivo) : ''}</span></li>`).join('')}
        </ol>`;

    // Solo se ofrecen las transiciones que el servidor declara permitidas.
    $('#detalle-acciones').innerHTML = c.transiciones.map((t) =>
        `<button type="button" class="btn ${t === 'cancelada' ? 'btn--peligro' : 'btn--primario'}" data-estado="${t}">${ACCIONES[t]}</button>`,
    ).join('') + '<button type="button" class="btn" data-cerrar>Cerrar</button>';

    $('#cancelar-bloque').hidden = true;
    $('#detalle-error').hidden = true;
}

$('#detalle-acciones').addEventListener('click', async (ev) => {
    const boton = ev.target.closest('button');
    if (!boton) return;
    if (boton.hasAttribute('data-cerrar')) {
        $('#dlg-detalle').close();
        return;
    }

    const nuevo = boton.dataset.estado;
    if (nuevo === 'cancelada') {
        $('#cancelar-bloque').hidden = false;
        $('#cancelar-motivo').focus();
        return;
    }
    await aplicarEstado(nuevo);
});

$('#btn-confirmar-cancelacion').addEventListener('click', () => aplicarEstado('cancelada', $('#cancelar-motivo').value.trim()));
$('#btn-volver').addEventListener('click', () => ($('#cancelar-bloque').hidden = true));

async function aplicarEstado(nuevo, motivo) {
    const r = await api.cambiarEstado(estado.citaAbierta.id, nuevo, motivo);
    if (!r.ok) {
        const caja = $('#detalle-error');
        caja.textContent = mensajeDeError(r);
        caja.hidden = false;
        return;
    }
    estado.citaAbierta = r.data.data;
    pintarDetalle(estado.citaAbierta);
    $('#cancelar-motivo').value = '';
    avisar(`Estado actualizado: ${ESTADOS[nuevo].etiqueta}.`, 'exito');
    calendario.refetchEvents();
}

// ---------------------------------------------------------------- filtros y listado (RQF-06)
$('#filtro-doctor').addEventListener('change', (ev) => {
    estado.doctorId = ev.target.value;
    calendario.refetchEvents();
});

$('#filtro-canceladas').addEventListener('change', (ev) => {
    estado.mostrarCanceladas = ev.target.checked;
    calendario.refetchEvents();
});

$('#form-listado').addEventListener('submit', async (ev) => {
    ev.preventDefault();
    const f = ev.target;
    const r = await api.listarCitas({ doctor_id: f.doctor_id.value, desde: f.desde.value, hasta: f.hasta.value });
    const cuerpo = $('#tabla-listado tbody');

    if (!r.ok) {
        cuerpo.innerHTML = `<tr><td colspan="6">${esc(mensajeDeError(r))}</td></tr>`;
        return;
    }
    const citas = r.data.data;
    $('#listado-total').textContent = `${citas.length} cita${citas.length === 1 ? '' : 's'}`;
    cuerpo.innerHTML = citas.length === 0
        ? '<tr><td colspan="6">No hay citas con esos filtros.</td></tr>'
        : citas.map((c) => `
            <tr>
                <td>${c.fecha}</td><td>${c.hora_inicio}–${c.hora_fin}</td>
                <td>${esc(c.paciente.nombre)}</td><td>${esc(c.doctor.nombre)}</td>
                <td><span class="insignia insignia--${c.estado}">${ESTADOS[c.estado].etiqueta}</span></td>
                <td><button type="button" class="btn btn--enlace" data-ver="${c.id}">Ver</button></td>
            </tr>`).join('');
});

$('#tabla-listado').addEventListener('click', (ev) => {
    const id = ev.target.closest('[data-ver]')?.dataset.ver;
    if (id) abrirDetalle(id);
});

document.querySelectorAll('[data-cerrar-dialogo]').forEach((b) =>
    b.addEventListener('click', () => b.closest('dialog').close()),
);

// ---------------------------------------------------------------- inicio
(function iniciarListado() {
    const hoy = new Date();
    const f = $('#form-listado');
    f.desde.value = fechaISO(hoy);
    f.hasta.value = fechaISO(new Date(hoy.getTime() + 14 * 86400000));
})();

cargarCatalogos();
calendario.render();
window.calendarioHis = calendario;
