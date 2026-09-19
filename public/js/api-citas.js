/**
 * Cliente HTTP de la API de citas.
 *
 * Única pieza del frontend que conoce las rutas de la API. El calendario no
 * llama a fetch directamente (RQNF-04).
 */
export class ApiCitas {
    constructor(base = '/api') {
        this.base = base;
    }

    async #pedir(metodo, ruta, cuerpo) {
        const opciones = {
            method: metodo,
            headers: { Accept: 'application/json' },
        };
        if (cuerpo !== undefined) {
            opciones.headers['Content-Type'] = 'application/json';
            opciones.body = JSON.stringify(cuerpo);
        }

        let respuesta;
        try {
            respuesta = await fetch(this.base + ruta, opciones);
        } catch (e) {
            return { ok: false, status: 0, data: { message: 'No hay conexión con el servidor.', code: 'SIN_CONEXION' } };
        }

        const data = await respuesta.json().catch(() => ({}));
        return { ok: respuesta.ok, status: respuesta.status, data };
    }

    listarCitas(filtros = {}) {
        const params = new URLSearchParams();
        Object.entries(filtros).forEach(([clave, valor]) => {
            if (valor !== null && valor !== undefined && valor !== '') params.append(clave, valor);
        });
        const qs = params.toString();
        return this.#pedir('GET', '/citas' + (qs ? '?' + qs : ''));
    }

    obtenerCita(id) {
        return this.#pedir('GET', `/citas/${id}`);
    }

    crearCita(datos) {
        return this.#pedir('POST', '/citas', datos);
    }

    reprogramarCita(id, datos) {
        return this.#pedir('PUT', `/citas/${id}`, datos);
    }

    cambiarEstado(id, estado, motivo) {
        return this.#pedir('PATCH', `/citas/${id}/estado`, motivo ? { estado, motivo } : { estado });
    }

    listarDoctores() {
        return this.#pedir('GET', '/doctores');
    }

    listarPacientes() {
        return this.#pedir('GET', '/pacientes');
    }
}
