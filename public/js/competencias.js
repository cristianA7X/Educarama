// public/js/competencias.js
//
// La pantalla de competencias (views/pages/gestionar_competencias.php).
// El HTML inicial lo arma PHP; acá solo se cambia lo que corresponde después
// de cada clic, que se guarda en el momento (de a una operación):
//
//  - "Elegir" / "Desarrollada": avanza la competencia un paso.
//  - "Volver atrás" (modo Corregir): la retrocede un paso.
//  - Casillas de acciones: marcar / desmarcar.
//
// Cada respuesta trae el resumen actualizado (contadores, avisos) y, a veces,
// sugerencias: "¿dar la competencia por desarrollada?", "¿confirmar la etapa?".
// Los avisos que piden confirmación (cupo, acciones sin cumplir) los maneja
// Educarama.ejecutar (public/js/educarama.js).

(function () {
    'use strict';

    const raiz = document.getElementById('competencias');
    if (!raiz || !window.Educarama) { return; }

    const idProtagonista = raiz.dataset.idProtagonista;
    const urlEtapas = raiz.dataset.urlEtapas;
    const nombresEtapas = JSON.parse(raiz.dataset.etapas || '{}');
    const ultimaEtapa = nombresEtapas[raiz.dataset.ultimaEtapa] ?? '';

    // Cómo se ve cada estado. Los textos son los de EstadoProgresion::etiqueta() y los de la página.
    const ESTADOS = {
        pendiente: { texto: 'Pendiente', color: 'secondary', avanzar: 'Elegir' },
        en_proceso: { texto: 'En proceso', color: 'warning', avanzar: 'Desarrollada' },
        desarrollada: { texto: 'Desarrollada', color: 'success', avanzar: null },
    };
    const SIGUIENTE = { pendiente: 'en_proceso', en_proceso: 'desarrollada' };
    const ANTERIOR = { desarrollada: 'en_proceso', en_proceso: 'pendiente' };

    const poner = (selector, valor) => raiz.querySelectorAll(selector).forEach((e) => { e.textContent = valor; });

    // ------------------------------------------------------------------
    // Dibujar
    // ------------------------------------------------------------------

    /** Muestra una competencia en su estado nuevo. `etiquetaEtapa` undefined = no la cambia. */
    function pintar(item, estado, etiquetaEtapa) {
        item.dataset.estado = estado;

        const badge = item.querySelector('[data-badge-estado]');
        badge.className = 'badge text-bg-' + ESTADOS[estado].color;
        badge.textContent = ESTADOS[estado].texto;

        if (etiquetaEtapa !== undefined) { item.querySelector('[data-etiqueta-etapa]').textContent = etiquetaEtapa; }

        const avanzar = item.querySelector('.btn-avanzar');
        if (avanzar) {
            avanzar.classList.toggle('d-none', ESTADOS[estado].avanzar === null);
            avanzar.querySelector('span').textContent = ESTADOS[estado].avanzar ?? '';
        }
        item.querySelector('.btn-retroceder')?.classList.toggle('invisible', estado === 'pendiente');

        // No se tildan acciones de una competencia pendiente: primero hay que elegirla.
        item.querySelectorAll('.casilla-accion').forEach((c) => { c.disabled = estado === 'pendiente'; });
        item.querySelector('.aviso-pendiente')?.classList.toggle('d-none', estado !== 'pendiente');

        aplicarFiltro();
    }

    /** Contadores y avisos de arriba, con el resumen que devolvió el servidor. */
    function repintar(resumen) {
        if (!resumen) { return; }

        poner('[data-contador="enCurso"]', resumen.enCursoDesarrolladas);
        poner('[data-contador="elegidas"]', resumen.enCursoElegidas);
        if (resumen.etapaEnCurso) {
            poner('[data-contador="cupo"]', resumen.etapaEnCurso.cupo);
            poner('[data-etapa-en-curso]', resumen.etapaEnCurso.titulo);
        }
        poner('[data-contador="total"]', resumen.desarrolladasTotal);
        const barra = raiz.querySelector('[data-barra-total]');
        if (barra && resumen.competenciasTotal > 0) {
            barra.style.width = Math.round(100 * resumen.desarrolladasTotal / resumen.competenciasTotal) + '%';
        }
        (resumen.areas ?? []).forEach((a) => poner('[data-area-desarrolladas="' + a.idArea + '"]', a.desarrolladas));

        const avisos = document.getElementById('avisosResumen');
        const esc = window.Educarama.esc;
        let html = '';
        if (resumen.insigniaPendiente && resumen.etapaEnCurso) {
            html += '<div class="alert alert-warning py-2 small mb-2"><i class="bi bi-award me-1" aria-hidden="true"></i>Tiene la etapa '
                + esc(resumen.etapaEnCurso.titulo) + ' en curso y todavía no recibió su insignia.</div>';
        }
        if (resumen.etapaLista && resumen.etapaEnCurso) {
            html += '<div class="alert alert-success py-2 small mb-2"><i class="bi bi-check2-circle me-1" aria-hidden="true"></i>Cumplió todo para confirmar la etapa '
                + esc(resumen.etapaEnCurso.titulo) + '. <a href="' + esc(urlEtapas) + '">Ir a confirmarla</a></div>';
        }
        avisos.innerHTML = html;
    }

    // ------------------------------------------------------------------
    // Filtro y modo Corregir
    // ------------------------------------------------------------------

    function aplicarFiltro() {
        const filtro = raiz.querySelector('input[name="filtro"]:checked')?.value ?? 'todas';
        raiz.querySelectorAll('.competencia').forEach((item) => {
            item.classList.toggle('d-none', filtro !== 'todas' && item.dataset.estado !== filtro);
        });
        raiz.querySelectorAll('.tab-pane').forEach((panel) => {
            const visibles = panel.querySelectorAll('.competencia:not(.d-none)').length;
            panel.querySelector('.sin-resultados')?.classList.toggle('d-none', visibles > 0);
        });
    }

    raiz.querySelectorAll('input[name="filtro"]').forEach((r) => r.addEventListener('change', aplicarFiltro));

    const botonCorregir = document.getElementById('botonCorregir');
    function modoCorregir(activo) {
        raiz.classList.toggle('modo-corregir', activo);
        if (botonCorregir) { botonCorregir.querySelector('span').textContent = activo ? 'Terminar de corregir' : 'Corregir'; }
    }
    botonCorregir?.addEventListener('click', () => modoCorregir(!raiz.classList.contains('modo-corregir')));
    document.getElementById('salirCorregir')?.addEventListener('click', () => modoCorregir(false));

    // ------------------------------------------------------------------
    // Guardar
    // ------------------------------------------------------------------

    /** Sugerencias del servidor: se ofrecen con un botón que hace lo sugerido. */
    async function sugerir(sugerencias, item) {
        for (const s of sugerencias ?? []) {
            if (s.tipo === 'competencia_lista' && item?.dataset.estado === 'en_proceso') {
                const si = await window.Educarama.confirmar({ titulo: s.mensaje, boton: 'Sí, dar por desarrollada', cancelar: 'Todavía no', icono: 'success' });
                if (si) { await avanzar(item); }
            } else if (s.tipo === 'etapa_lista') {
                const si = await window.Educarama.confirmar({ titulo: s.mensaje, boton: 'Ir a confirmar la etapa', cancelar: 'Más tarde', icono: 'success' });
                if (si) { window.location.assign(urlEtapas); }
            } else {
                window.Educarama.aviso(s.mensaje, 'info');
            }
        }
    }

    async function avanzar(item) {
        const destino = SIGUIENTE[item.dataset.estado];
        if (!destino) { return; }

        item.classList.add('guardando');
        const r = await window.Educarama.ejecutar(
            'progresion.avanzarCompetencia',
            { id_protagonista: idProtagonista, id_competencia: item.dataset.id },
            { tituloConfirmar: '¿Registrar igual?', botonConfirmar: 'Sí, registrar igual' },
        );
        item.classList.remove('guardando');
        if (!r) { return; }

        const resumen = r.datos.resumen;
        // Al elegirla toma la etapa en curso; si ya logró todas, la última de la rama.
        const etiqueta = destino === 'en_proceso' ? 'Elegida en ' + (resumen.etapaEnCurso?.titulo ?? ultimaEtapa) : undefined;
        pintar(item, destino, etiqueta);
        repintar(resumen);
        await sugerir(r.datos.sugerencias, item);
    }

    async function retroceder(item) {
        const destino = ANTERIOR[item.dataset.estado];
        if (!destino) { return; }

        item.classList.add('guardando');
        const r = await window.Educarama.ejecutar(
            'progresion.retrocederCompetencia',
            { id_protagonista: idProtagonista, id_competencia: item.dataset.id },
        );
        item.classList.remove('guardando');
        if (!r) { return; }

        pintar(item, destino, destino === 'pendiente' ? '' : undefined);
        repintar(r.datos.resumen);
    }

    raiz.addEventListener('click', (ev) => {
        const boton = ev.target.closest('.btn-avanzar, .btn-retroceder');
        if (!boton) { return; }
        const item = boton.closest('.competencia');
        if (boton.classList.contains('btn-avanzar')) { avanzar(item); } else { retroceder(item); }
    });

    raiz.addEventListener('change', async (ev) => {
        const casilla = ev.target.closest('.casilla-accion');
        if (!casilla) { return; }
        const item = casilla.closest('.competencia');
        const marcar = casilla.checked;

        casilla.disabled = true;
        const r = await window.Educarama.ejecutar(
            marcar ? 'progresion.marcarAccion' : 'progresion.desmarcarAccion',
            { id_protagonista: idProtagonista, id_accion: casilla.dataset.idAccion },
        );
        casilla.disabled = item.dataset.estado === 'pendiente';

        if (!r) { casilla.checked = !marcar; return; }   // No se guardó: vuelve a como estaba

        const contador = item.querySelector('[data-acciones-cumplidas]');
        if (contador) { contador.textContent = item.querySelectorAll('.casilla-accion:checked').length; }
        repintar(r.datos.resumen);
        await sugerir(r.datos.sugerencias, item);
    });
})();
