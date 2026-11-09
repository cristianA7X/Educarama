// public/js/educarama.js
//
// JavaScript compartido por todas las páginas. Hace tres cosas:
//
//  1. Habla con public/api.php (llamar / ejecutar), con el token CSRF y el
//     flujo de confirmación de los avisos que se pueden saltear (409).
//  2. Conecta formularios y botones declarados con atributos data-*, así la
//     mayoría de las páginas no necesita JavaScript propio:
//
//       <form data-accion="usuario.iniciarSesion"> ... </form>
//       <button data-accion="documento.eliminar" data-datos='{"id_documento":3}'
//               data-confirmar="¿Eliminar el documento?" data-peligro>Eliminar</button>
//
//     Atributos: data-accion (obligatorio), data-datos (JSON con los campos de un
//     botón), data-confirmar (pregunta antes de enviar), data-texto, data-boton
//     (texto del botón de confirmar), data-peligro (botón rojo). Un formulario
//     también acepta data-reiniciar (se vacía al terminar) y data-recargar
//     (recarga la página al terminar).
//  3. Maneja el menú lateral (drawer) y el botón de mostrar/ocultar contraseña.
//
// Las confirmaciones y avisos usan SweetAlert2 (se carga en pie.php). Si no
// cargó (sin internet), se usan los cuadros nativos del navegador.

(function () {
    'use strict';

    // Los colores salen de las variables de public/css/styles.css, para que el
    // botón de SweetAlert2 no se desfase si cambia la paleta.
    const variableCss = (nombre) => getComputedStyle(document.documentElement).getPropertyValue(nombre).trim();
    const COLOR_PRIMARIO = variableCss('--violeta');
    const COLOR_PELIGRO = variableCss('--bs-danger'); // rojo de peligro de Bootstrap
    const MENSAJE_INESPERADO = 'Ocurrió un error inesperado. Probá de nuevo en un rato.';

    const meta = (nombre) => document.querySelector('meta[name="' + nombre + '"]')?.content ?? '';
    const haySwal = () => typeof window.Swal !== 'undefined';

    /** Escapa un texto para meterlo en HTML. */
    function esc(texto) {
        const d = document.createElement('div');
        d.textContent = String(texto ?? '');
        return d.innerHTML;
    }

    // ------------------------------------------------------------------
    // Avisos y confirmaciones
    // ------------------------------------------------------------------

    /** Aviso corto arriba a la derecha que se va solo. */
    function aviso(mensaje, icono = 'success') {
        if (!haySwal()) { window.alert(mensaje); return; }
        window.Swal.fire({
            toast: true, position: 'top-end', icon: icono, title: mensaje,
            showConfirmButton: false, timer: 3500, timerProgressBar: true,
        });
    }

    /** Error para el usuario (siempre un texto de ServicioException o el genérico). */
    function error(mensaje, titulo = 'No se pudo completar') {
        if (!haySwal()) { window.alert(mensaje); return Promise.resolve(); }
        return window.Swal.fire({ icon: 'error', title: titulo, text: mensaje, confirmButtonColor: COLOR_PRIMARIO });
    }

    /**
     * Pregunta antes de hacer algo. Devuelve una promesa con true/false.
     * `html` ya tiene que venir escapado (usar esc()).
     */
    async function confirmar({ titulo, texto = '', html = null, boton = 'Sí', cancelar = 'Cancelar', peligro = false, icono = 'question' }) {
        if (!haySwal()) { return window.confirm(titulo + (texto ? '\n' + texto : '')); }
        const r = await window.Swal.fire({
            icon: icono, title: titulo, text: html === null ? texto : undefined, html: html ?? undefined,
            showCancelButton: true, confirmButtonText: boton, cancelButtonText: cancelar,
            confirmButtonColor: peligro ? COLOR_PELIGRO : COLOR_PRIMARIO, focusCancel: peligro,
        });
        return r.isConfirmed;
    }

    // ------------------------------------------------------------------
    // Pedidos a api.php
    // ------------------------------------------------------------------

    /** {a: 1, b: [2, 3], c: true} → FormData (arrays como b[], booleanos como '1' o ''). */
    function aFormData(datos) {
        if (datos instanceof FormData) { return datos; }
        const fd = new FormData();
        for (const [clave, valor] of Object.entries(datos)) {
            if (Array.isArray(valor)) { valor.forEach((v) => fd.append(clave + '[]', v)); }
            else if (typeof valor === 'boolean') { fd.append(clave, valor ? '1' : ''); }
            else if (valor !== null && valor !== undefined) { fd.append(clave, valor); }
        }
        return fd;
    }

    /**
     * Un pedido POST a api.php?accion=... con el token CSRF. No muestra nada:
     * devuelve el JSON de la respuesta más `status` (el código HTTP). Un fallo
     * de red o una respuesta que no es JSON se convierte en {ok: false, mensaje}.
     */
    async function llamar(accion, datos = {}) {
        const fd = aFormData(datos);
        // El formulario trae su campo csrf_token (Csrf::campo()); un botón suelto usa el de la cabecera.
        if (!fd.has('csrf_token')) { fd.set('csrf_token', meta('csrf-token')); }

        let respuesta;
        try {
            respuesta = await fetch(meta('url-api') + '?accion=' + encodeURIComponent(accion), {
                method: 'POST', body: fd, credentials: 'same-origin', headers: { Accept: 'application/json' },
            });
        } catch (e) {
            return { ok: false, status: 0, mensaje: 'No hay conexión con el servidor. Revisá tu internet e intentá de nuevo.' };
        }

        let cuerpo;
        try { cuerpo = await respuesta.json(); } catch (e) { cuerpo = { ok: false, mensaje: MENSAJE_INESPERADO }; }
        cuerpo.status = respuesta.status;
        return cuerpo;
    }

    /**
     * Hace el pedido y se ocupa de todo lo que sigue:
     *  - Si faltan requisitos que se pueden saltear (409), muestra los avisos y,
     *    si el usuario acepta, repite el pedido con confirmarIgual=1.
     *  - Si la respuesta trae `redireccion`, va a esa página (el mensaje se
     *    muestra allá, lo deja el Flash del servidor).
     *  - Sin sesión (401), va al login.
     *  - Cualquier otro error se muestra y se devuelve null.
     *
     * Devuelve el JSON si salió bien, o null si no.
     * `opciones.tituloConfirmar` / `opciones.botonConfirmar` cambian el texto del 409.
     */
    async function ejecutar(accion, datos = {}, opciones = {}) {
        const fd = aFormData(datos);

        for (;;) {
            const r = await llamar(accion, fd);

            if (r.ok) {
                if (r.redireccion) { window.location.assign(r.redireccion); }
                return r;
            }
            if (r.status === 401) { window.location.assign(meta('url-login')); return null; }

            if (r.confirmar && !fd.has('confirmarIgual')) {
                const lista = '<ul class="text-start mb-0">' + (r.avisos ?? []).map((a) => '<li>' + esc(a.mensaje) + '</li>').join('') + '</ul>';
                const sigue = await confirmar({
                    titulo: opciones.tituloConfirmar ?? 'Antes de seguir',
                    html: lista, icono: 'warning',
                    boton: opciones.botonConfirmar ?? 'Sí, continuar',
                });
                if (!sigue) { return null; }
                fd.set('confirmarIgual', '1');
                continue;
            }

            await error(r.mensaje || MENSAJE_INESPERADO);
            return null;
        }
    }

    /** API pública, por si una página necesita algo propio. */
    window.Educarama = { llamar, ejecutar, confirmar, aviso, error, esc };

    // ------------------------------------------------------------------
    // Formularios y botones declarados con data-accion
    // ------------------------------------------------------------------

    async function confirmarSiPide(elemento) {
        if (!elemento.dataset.confirmar) { return true; }
        return confirmar({
            titulo: elemento.dataset.confirmar, texto: elemento.dataset.texto ?? '',
            boton: elemento.dataset.boton ?? 'Sí', peligro: 'peligro' in elemento.dataset,
            icono: 'peligro' in elemento.dataset ? 'warning' : 'question',
        });
    }

    /** Después de un pedido bien hecho sin redirección: el mensaje del servidor, y recargar si se pidió. */
    function alTerminar(elemento, respuesta) {
        if (!respuesta || respuesta.redireccion) { return; }
        if (respuesta.mensaje) { aviso(respuesta.mensaje); }
        if ('reiniciar' in elemento.dataset && typeof elemento.reset === 'function') { elemento.reset(); }
        if ('recargar' in elemento.dataset) { window.location.reload(); }
    }

    document.addEventListener('submit', async (ev) => {
        const form = ev.target.closest('form[data-accion]');
        if (!form) { return; }
        ev.preventDefault();

        if (!(await confirmarSiPide(form))) { return; }

        const botones = form.querySelectorAll('[type="submit"]');
        botones.forEach((b) => { b.disabled = true; });
        const respuesta = await ejecutar(form.dataset.accion, new FormData(form, ev.submitter));
        // Si va a redirigir, los botones quedan bloqueados hasta que cambie la página (evita un doble envío).
        if (!respuesta?.redireccion) { botones.forEach((b) => { b.disabled = false; }); }
        alTerminar(form, respuesta);
    });

    document.addEventListener('click', async (ev) => {
        const elemento = ev.target.closest('[data-accion]:not(form)');
        if (!elemento) { return; }
        ev.preventDefault();

        if (!(await confirmarSiPide(elemento))) { return; }

        let datos = {};
        try { datos = elemento.dataset.datos ? JSON.parse(elemento.dataset.datos) : {}; } catch (e) { datos = {}; }

        elemento.disabled = true;
        const respuesta = await ejecutar(elemento.dataset.accion, datos);
        if (!respuesta?.redireccion) { elemento.disabled = false; }
        alTerminar(elemento, respuesta);
    });

    // ------------------------------------------------------------------
    // Mostrar u ocultar una contraseña:
    //   <button type="button" data-ver-contrasenia="#contrasenia"><i class="bi bi-eye"></i></button>
    // ------------------------------------------------------------------

    document.addEventListener('click', (ev) => {
        const boton = ev.target.closest('[data-ver-contrasenia]');
        if (!boton) { return; }
        const campo = document.querySelector(boton.dataset.verContrasenia);
        if (!campo) { return; }
        const visible = campo.type === 'password';
        campo.type = visible ? 'text' : 'password';
        boton.setAttribute('aria-pressed', String(visible));
        boton.querySelector('i')?.classList.toggle('bi-eye', !visible);
        boton.querySelector('i')?.classList.toggle('bi-eye-slash', visible);
    });

    // ------------------------------------------------------------------
    // Campo de fecha en tres listas (Vista::campoFecha):
    //   <div data-campo-fecha data-max="AAAA-MM-DD"> hidden + día, mes y año
    // Cada cambio arma la fecha en el campo oculto, que es lo que se envía.
    // Una fecha incompleta, inexistente o futura deja la lista inválida, y el
    // navegador no deja enviar el formulario hasta corregirla.
    // ------------------------------------------------------------------

    document.addEventListener('change', (ev) => {
        const campo = ev.target.closest('[data-campo-fecha]');
        if (!campo || !ev.target.matches('select[data-parte]')) { return; }

        const oculto = campo.querySelector('input[type="hidden"]');
        const lista = (parte) => campo.querySelector('[data-parte="' + parte + '"]');
        const [dia, mes, anio] = [lista('dia'), lista('mes'), lista('anio')];
        const d = Number(dia.value), m = Number(mes.value), a = Number(anio.value);
        const dos = (n) => String(n).padStart(2, '0');

        // Los días que no existen en el mes elegido quedan deshabilitados.
        const diasDelMes = new Date(a || 2000, m || 1, 0).getDate(); // año bisiesto si no hay año
        dia.querySelectorAll('option[value]:not([value=""])').forEach((o) => { o.disabled = Number(o.value) > diasDelMes; });

        [dia, mes, anio].forEach((s) => s.setCustomValidity(''));
        oculto.value = '';

        const elegidas = [d, m, a].filter(Boolean).length;
        if (elegidas === 0) { return; } // opcional: los tres vacíos es "sin fecha"
        if (elegidas < 3) {
            [dia, mes, anio].find((s) => !s.value).setCustomValidity('Completá día, mes y año, o dejá los tres vacíos.');
            return;
        }
        if (d > diasDelMes) {
            dia.setCustomValidity('Ese mes tiene ' + diasDelMes + ' días.');
            return;
        }

        const fecha = a + '-' + dos(m) + '-' + dos(d);
        if (campo.dataset.max && fecha > campo.dataset.max) {
            dia.setCustomValidity('La fecha no puede ser futura.');
            return;
        }
        oculto.value = fecha;
    });

    // ------------------------------------------------------------------
    // Pestañas: la del # de la URL se abre sola (ej. perfil_protagonista.php?id=8#documentos),
    // y al cambiar de pestaña se actualiza el #, así recargar o volver deja la misma.
    // ------------------------------------------------------------------

    document.addEventListener('DOMContentLoaded', () => {
        const pestanias = document.querySelectorAll('[data-bs-toggle="tab"][data-bs-target^="#"]');
        if (pestanias.length === 0 || !window.bootstrap) { return; }

        const elegida = window.location.hash
            ? document.querySelector('[data-bs-toggle="tab"][data-bs-target="' + CSS.escape(window.location.hash) + '"]')
            : null;
        if (elegida) { window.bootstrap.Tab.getOrCreateInstance(elegida).show(); }

        pestanias.forEach((p) => p.addEventListener('shown.bs.tab', () => {
            window.history.replaceState(null, '', p.dataset.bsTarget);
        }));
    });

    // ------------------------------------------------------------------
    // Menú lateral (drawer)
    //
    // Desde 992 px está a la vista y se oculta con la clase html.menu-oculto
    // (se recuerda en localStorage). Por debajo, es el offcanvas de Bootstrap.
    // ------------------------------------------------------------------

    document.addEventListener('DOMContentLoaded', () => {
        const menu = document.getElementById('menuLateral');
        const boton = document.getElementById('botonMenu');
        if (!menu || !boton) { return; }

        const raiz = document.documentElement;
        const escritorio = window.matchMedia('(min-width: 992px)');
        const offcanvas = () => window.bootstrap.Offcanvas.getOrCreateInstance(menu);
        const abierto = () => (escritorio.matches ? !raiz.classList.contains('menu-oculto') : menu.classList.contains('show'));
        const actualizarAria = () => boton.setAttribute('aria-expanded', String(abierto()));

        boton.addEventListener('click', () => {
            if (escritorio.matches) {
                const oculto = raiz.classList.toggle('menu-oculto');
                try { localStorage.setItem('educarama.menu', oculto ? 'oculto' : 'visible'); } catch (e) { /* sin almacenamiento: no se recuerda */ }
            } else {
                offcanvas().toggle();
            }
            actualizarAria();
        });

        menu.addEventListener('shown.bs.offcanvas', actualizarAria);
        menu.addEventListener('hidden.bs.offcanvas', actualizarAria);

        // Al agrandar la ventana, si el cajón estaba abierto se cierra: en escritorio el menú ya está a la vista.
        escritorio.addEventListener('change', () => {
            if (escritorio.matches && menu.classList.contains('show')) { offcanvas().hide(); }
            actualizarAria();
        });

        actualizarAria();
        // Las animaciones se activan después del primer dibujo: si no, el menú "se desliza" en cada página.
        window.requestAnimationFrame(() => window.requestAnimationFrame(() => raiz.classList.add('menu-listo')));
    });
})();
