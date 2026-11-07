// public/js/solicitud_alta.js
//
// Las filas de secciones del formulario de solicitud de alta: agregar otra
// (copia el <template>) y quitar una. Siempre queda al menos una fila.
// El envío lo maneja educarama.js (data-accion en el formulario).

(function () {
    'use strict';

    const contenedor = document.getElementById('filasSecciones');
    const plantilla = document.getElementById('plantillaSeccion');
    const agregar = document.getElementById('agregarSeccion');
    if (!contenedor || !plantilla || !agregar) { return; }

    agregar.addEventListener('click', () => {
        const fila = plantilla.content.firstElementChild.cloneNode(true);
        contenedor.appendChild(fila);
        fila.querySelector('input')?.focus();
    });

    contenedor.addEventListener('click', (ev) => {
        const boton = ev.target.closest('[data-quitar-seccion]');
        if (!boton) { return; }

        const filas = contenedor.querySelectorAll('.fila-seccion');
        if (filas.length <= 1) {
            // La última no se quita: se vacía, así nunca se envía sin secciones por accidente.
            filas[0].querySelectorAll('input').forEach((i) => { i.value = ''; });
            filas[0].querySelectorAll('select').forEach((s) => { s.value = ''; });
            return;
        }
        boton.closest('.fila-seccion').remove();
    });
})();
