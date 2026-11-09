<?php
// views/includes/pie.php
//
// Cierra lo que abrió views/includes/cabecera.php: el contenedor, el pie de
// página y los scripts. Una página con JavaScript propio lo declara antes de
// incluir este archivo:
//
//   $scriptsPagina = ['public/js/competencias.js'];

declare(strict_types=1);

$scriptsPagina = $scriptsPagina ?? [];
?>
            </div>
        </main>

        <footer class="footer-inst">
            <?php // Aviso de proyecto académico: el pie no puede sugerir respaldo institucional. ?>
            <p class="m-0 texto-blanco small">
                © <?= date('Y') ?> Educarama · Proyecto académico sin vinculación oficial con Scouts de Argentina Asociación Civil.<br>
                El programa educativo y los nombres de ramas, etapas y competencias pertenecen a
                <a href="https://scouts.org.ar" target="_blank" rel="noopener">Scouts de Argentina Asociación Civil</a>.
            </p>
        </footer>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.10.8/dist/sweetalert2.all.min.js"></script>
    <script src="<?= e(url('public/js/educarama.js')) ?>?v=<?= (int) @filemtime(__DIR__ . '/../../public/js/educarama.js') ?>"></script>
    <?php foreach ($scriptsPagina as $scriptPagina): ?>
        <script src="<?= e(url($scriptPagina)) ?>?v=<?= (int) @filemtime(__DIR__ . '/../../' . $scriptPagina) ?>"></script>
    <?php endforeach; ?>
</body>
</html>
