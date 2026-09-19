<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Control de Citas Médicas — HIS</title>
    <link rel="stylesheet" href="{{ asset('css/calendario.css') }}">
    <script src="{{ asset('vendor/fullcalendar-6.1.21/index.global.min.js') }}"></script>
    <script src="{{ asset('vendor/fullcalendar-6.1.21/locale-es.global.min.js') }}"></script>
</head>
<body>
<header class="barra">
    <div>
        <h1>Control de Citas Médicas</h1>
        <p class="barra__sub">Sistema Hospitalario Integrado</p>
    </div>
    <button type="button" class="btn btn--primario" id="btn-nueva">+ Nueva cita</button>
</header>

<main class="contenido">
    <aside class="panel" aria-label="Filtros y leyenda">
        <section>
            <h2>Filtrar calendario</h2>
            <label for="filtro-doctor">Doctor</label>
            <select id="filtro-doctor"><option value="">Todos los doctores</option></select>
            <label class="casilla"><input type="checkbox" id="filtro-canceladas" checked> Mostrar canceladas</label>
            <p class="ayuda" id="resumen" aria-live="polite"></p>
        </section>

        <section>
            <h2>Estados</h2>
            <ul class="leyenda">
                <li><span class="insignia insignia--pendiente">Pendiente</span></li>
                <li><span class="insignia insignia--confirmada">Confirmada</span></li>
                <li><span class="insignia insignia--atendida">Atendida</span></li>
                <li><span class="insignia insignia--cancelada">Cancelada</span></li>
            </ul>
        </section>

        <section>
            <h2>Cómo usarlo</h2>
            <ul class="ayuda">
                <li>Seleccione un horario vacío para crear una cita.</li>
                <li>Haga clic en una cita para ver el detalle y cambiar su estado.</li>
                <li>Arrastre una cita para reprogramarla, o estire su borde para cambiar la duración.</li>
            </ul>
        </section>
    </aside>

    <section class="principal">
        <div id="calendario" aria-label="Calendario de citas"></div>

        <section class="listado" aria-labelledby="listado-titulo">
            <h2 id="listado-titulo">Listado por doctor y rango de fechas</h2>
            <form id="form-listado" class="filtros-listado">
                <div><label for="listado-doctor">Doctor</label><select id="listado-doctor" name="doctor_id"></select></div>
                <div><label for="listado-desde">Desde</label><input type="date" id="listado-desde" name="desde"></div>
                <div><label for="listado-hasta">Hasta</label><input type="date" id="listado-hasta" name="hasta"></div>
                <div><button type="submit" class="btn btn--primario">Buscar</button></div>
            </form>
            <p class="ayuda" id="listado-total" aria-live="polite"></p>
            <div class="tabla-envoltura">
                <table id="tabla-listado">
                    <thead><tr><th>Fecha</th><th>Horario</th><th>Paciente</th><th>Doctor</th><th>Estado</th><th><span class="sr">Acción</span></th></tr></thead>
                    <tbody><tr><td colspan="6">Elija los filtros y pulse Buscar.</td></tr></tbody>
                </table>
            </div>
        </section>
    </section>
</main>

<div id="avisos" class="avisos" aria-live="polite"></div>

<dialog id="dlg-crear" aria-labelledby="crear-titulo">
    <form id="form-crear" novalidate>
        <h2 id="crear-titulo">Nueva cita</h2>
        <div class="campo">
            <label for="crear-paciente">Paciente</label>
            <select id="crear-paciente" name="paciente_id" required></select>
            <span class="error-campo" data-error-de="paciente_id"></span>
        </div>
        <div class="campo">
            <label for="crear-doctor">Doctor</label>
            <select id="crear-doctor" name="doctor_id" required></select>
            <span class="error-campo" data-error-de="doctor_id"></span>
        </div>
        <div class="fila">
            <div class="campo">
                <label for="crear-fecha">Fecha</label>
                <input type="date" id="crear-fecha" name="fecha" required>
                <span class="error-campo" data-error-de="fecha"></span>
            </div>
            <div class="campo">
                <label for="crear-inicio">Inicio</label>
                <input type="time" id="crear-inicio" name="hora_inicio" step="300" required>
                <span class="error-campo" data-error-de="hora_inicio"></span>
            </div>
            <div class="campo">
                <label for="crear-fin">Fin</label>
                <input type="time" id="crear-fin" name="hora_fin" step="300" required>
                <span class="error-campo" data-error-de="hora_fin"></span>
            </div>
        </div>
        <div class="campo">
            <label for="crear-motivo">Motivo</label>
            <textarea id="crear-motivo" name="motivo" rows="2" maxlength="255" required></textarea>
            <span class="error-campo" data-error-de="motivo"></span>
        </div>
        <p class="error-general" id="crear-error" role="alert" hidden></p>
        <div class="acciones">
            <button type="button" class="btn" data-cerrar-dialogo>Cerrar</button>
            <button type="submit" class="btn btn--primario">Agendar cita</button>
        </div>
    </form>
</dialog>

<dialog id="dlg-detalle" aria-labelledby="detalle-titulo">
    <h2 id="detalle-titulo">Cita</h2>
    <div id="detalle-cuerpo"></div>
    <div id="cancelar-bloque" class="cancelar" hidden>
        <label for="cancelar-motivo">Motivo de la cancelación</label>
        <textarea id="cancelar-motivo" rows="2" maxlength="255"></textarea>
        <div class="acciones">
            <button type="button" class="btn" id="btn-volver">Volver sin cancelar</button>
            <button type="button" class="btn btn--peligro" id="btn-confirmar-cancelacion">Sí, cancelar cita</button>
        </div>
    </div>
    <p class="error-general" id="detalle-error" role="alert" hidden></p>
    <div class="acciones" id="detalle-acciones"></div>
</dialog>

<script type="module" src="{{ asset('js/calendario.js') }}"></script>
</body>
</html>
