<?php
/**
 * @var int $totalProductos
 * @var int $totalUnidades
 * @var float $valorTotalInventario
 * @var array $stockBajo
 */
?>
<div class="page-heading"><div><h1>Buen día, bienvenida 🌿</h1><p>Este es el resumen de tu vivero. Revisá el estado del inventario de un vistazo.</p></div><a href="index.php?action=inventario" class="btn btn-success"><i class="bi bi-box-seam me-1"></i> Ver inventario</a></div>

<!-- Tarjetas de Estadísticas (KPIs) -->
<div class="row g-4 mb-4">
    <div class="col-md-4">
        <div class="card kpi-card bg-white">
            <div class="d-flex justify-content-between align-items-center"><h6 class="kpi-label mb-0">Productos registrados</h6><span class="kpi-icon" style="background:#eaf5ed;color:#236b45"><i class="bi bi-flower1"></i></span></div>
            <h3 class="kpi-value" style="color:#236b45"><?= $totalProductos ?></h3>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card kpi-card bg-white">
            <div class="d-flex justify-content-between align-items-center"><h6 class="kpi-label mb-0">Unidades disponibles</h6><span class="kpi-icon" style="background:#eaf2fc;color:#3978bd"><i class="bi bi-boxes"></i></span></div>
            <h3 class="kpi-value" style="color:#3978bd"><?= $totalUnidades ?></h3>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card kpi-card bg-white">
            <div class="d-flex justify-content-between align-items-center"><h6 class="kpi-label mb-0">Valor estimado del stock</h6><span class="kpi-icon" style="background:#fff3dc;color:#a86b12"><i class="bi bi-currency-dollar"></i></span></div>
            <h3 class="kpi-value" style="color:#20342a">$<?= number_format($valorTotalInventario, 2, ',', '.') ?></h3>
        </div>
    </div>
</div>

<!-- Alertas de Stock Bajo -->
<div class="card mb-4">
    <div class="card-header py-3 px-4">
        <h5 class="mb-0 fw-bold"><i class="bi bi-exclamation-triangle-fill me-2 text-danger"></i>Alertas de stock bajo</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Categoría</th>
                        <th>Producto</th>
                        <th>Stock Actual</th>
                        <th>Stock Mínimo</th>
                        <th>Ubicación</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($stockBajo)): ?>
                        <tr>
                            <td colspan="5" class="text-center py-4 text-muted">¡Excelente! No hay productos con stock crítico.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($stockBajo as $item): ?>
                            <tr>
                                <td><?= htmlspecialchars($item['categoria']) ?></td>
                                <td class="fw-bold"><?= htmlspecialchars($item['nombre']) ?></td>
                                <td><span class="badge bg-danger"><?= $item['stock'] ?></span></td>
                                <td><?= $item['stock_minimo'] ?></td>
                                <td><?= htmlspecialchars($item['ubicacion']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>