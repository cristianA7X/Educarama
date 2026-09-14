<!-- tomamos los eventos que ocurriran, filtramos por los que tienen fecha en las proximas 2 semanas -->
    <ul>
    <?php if (!empty($eventos)): ?>
        <?php foreach ($eventos as $evento): ?>
            <?php if (isset($evento['fecha']) && $evento['fecha'] >= date('Y-m-d') && $evento['fecha'] <= date('Y-m-d', strtotime('+14 days'))): ?>
                <li>
                    <strong><?php echo htmlspecialchars($evento['nombre']); ?></strong> - 
                    <?php echo htmlspecialchars($evento['fecha']); ?> - 
                    <?php echo htmlspecialchars($evento['descripcion']); ?>
                </li>
            <?php endif; ?>
        <?php endforeach; ?>
    <?php else: ?>
        <li>No hay eventos próximos.</li>
    <?php endif; ?>
    </ul>