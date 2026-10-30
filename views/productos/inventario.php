<div class="page-heading">
    <div><h1><i class="bi bi-box-seam me-2" style="color:var(--green)"></i>Inventario</h1><p>Controlá productos, existencias y movimientos del vivero.</p></div>
    <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalNuevoProducto"><i class="bi bi-plus-lg me-1"></i> Nuevo producto</button>
</div>
<?php if (!empty($_GET['mensaje'])): ?>
<div class="alert alert-<?= ($_GET['tipo'] ?? '') === 'error' ? 'danger' : 'success' ?> alert-dismissible fade show" role="alert">
    <?= htmlspecialchars($_GET['mensaje'], ENT_QUOTES, 'UTF-8') ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>
<div class="card mb-4"><div class="card-header py-3 px-4 d-flex justify-content-between align-items-center"><div><strong>Productos registrados</strong><div class="text-muted small mt-1"><?= count($productos) ?> producto(s) en el inventario</div></div><span class="badge rounded-pill text-bg-light"><i class="bi bi-arrow-repeat me-1"></i>Stock actualizado al cargar</span></div>
<?php if (!empty($productos)): ?>
<!-- Filtro del inventario: se resuelve en el navegador, sin pedir datos al servidor. -->
<div class="px-4 py-3 border-bottom">
    <div class="row g-2 align-items-center">
        <div class="col-md-5">
            <div class="input-group">
                <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                <input type="search" id="filtroTexto" class="form-control" placeholder="Buscar por nombre o ubicación…" autocomplete="off" aria-label="Buscar producto">
            </div>
        </div>
        <div class="col-md-4">
            <select id="filtroCategoria" class="form-select" aria-label="Filtrar por categoría">
                <option value="">Todas las categorías</option>
                <?php foreach (['Plantas y Flores','Árboles Frutales','Macetas y Jardineras','Sustratos y Tierra','Herramientas e Insumos'] as $cat): ?>
                <option value="<?= htmlspecialchars($cat, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($cat, ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <div class="form-check form-switch mb-0">
                <input class="form-check-input" type="checkbox" role="switch" id="filtroCritico">
                <label class="form-check-label" for="filtroCritico">Solo stock crítico</label>
            </div>
        </div>
    </div>
    <div class="small text-muted mt-2" id="filtroResultado" aria-live="polite"></div>
</div>
<?php endif; ?>
<div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead><tr><th>Producto</th><th>Categoría</th><th>Ubicación</th><th>Precio</th><th>Stock</th><th class="text-end">Acciones</th></tr></thead><tbody>
<?php if (empty($productos)): ?><tr><td colspan="6" class="text-center py-5"><i class="bi bi-flower2 fs-2 d-block mb-2 text-success"></i><strong>No hay productos todavía</strong><div class="text-muted mt-1">Agregá el primer producto para comenzar.</div></td></tr>
<?php else: foreach ($productos as $p): ?>
<tr class="fila-producto"
    data-texto="<?= htmlspecialchars($p['nombre'] . ' ' . ($p['ubicacion'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
    data-categoria="<?= htmlspecialchars($p['categoria'], ENT_QUOTES, 'UTF-8') ?>"
    data-critico="<?= (int)$p['stock'] <= (int)$p['stock_minimo'] ? '1' : '0' ?>">
<td><div class="d-flex align-items-center gap-3"><?php
    // Solo se pide la imagen si el archivo existe; si no, se muestra un ícono (evita errores 404).
    $img = basename((string)($p['imagen'] ?? ''));
    $tieneImagen = $img !== '' && $img !== 'default.jpg' && is_file(__DIR__ . '/../../assets/img/uploads/' . $img);
?><?php if ($tieneImagen): ?><img class="product-thumb" src="assets/img/uploads/<?= htmlspecialchars($img, ENT_QUOTES, 'UTF-8') ?>" alt=""><?php else: ?><div class="product-thumb d-grid text-success" style="place-items:center" aria-hidden="true"><i class="bi bi-flower1"></i></div><?php endif; ?><div><div class="fw-bold"><?= htmlspecialchars($p['nombre'], ENT_QUOTES, 'UTF-8') ?></div><small class="text-muted">ID #<?= (int)$p['id'] ?></small></div></div></td>
<td><span class="badge badge-category"><?= htmlspecialchars($p['categoria'], ENT_QUOTES, 'UTF-8') ?></span></td><td><?= htmlspecialchars($p['ubicacion'] ?? 'Depósito General', ENT_QUOTES, 'UTF-8') ?></td><td class="fw-bold">$<?= number_format((float)$p['precio'], 2, ',', '.') ?></td>
<td><span class="stock-badge <?= (int)$p['stock'] <= (int)$p['stock_minimo'] ? 'bg-danger-subtle text-danger' : 'bg-success-subtle text-success' ?>"><?= (int)$p['stock'] ?> un.</span><?php if ((int)$p['stock'] <= (int)$p['stock_minimo']): ?><div class="small text-danger mt-1">Stock crítico</div><?php endif; ?></td>
<td class="text-end text-nowrap"><button type="button" class="btn btn-sm btn-light border" data-bs-toggle="modal" data-bs-target="#modalEditar<?= (int)$p['id'] ?>" title="Modificar"><i class="bi bi-pencil"></i></button> <button type="button" class="btn btn-sm btn-light border text-success" data-bs-toggle="modal" data-bs-target="#modalReponer<?= (int)$p['id'] ?>" title="Reponer stock"><i class="bi bi-plus-lg"></i></button> <button type="button" class="btn btn-sm btn-light border text-warning-emphasis" data-bs-toggle="modal" data-bs-target="#modalDescontar<?= (int)$p['id'] ?>" title="Registrar venta"><i class="bi bi-cart-dash"></i></button> <button type="button" class="btn btn-sm btn-light border text-danger" data-bs-toggle="modal" data-bs-target="#modalEliminar<?= (int)$p['id'] ?>" title="Eliminar"><i class="bi bi-trash"></i></button></td>
</tr>
<?php endforeach; endif; ?>
<tr id="filaSinResultados" class="d-none"><td colspan="6" class="text-center py-5"><i class="bi bi-search fs-2 d-block mb-2 text-muted"></i><strong>No hay productos que coincidan</strong><div class="text-muted mt-1">Probá con otro nombre o quitá los filtros.</div></td></tr>
</tbody></table></div></div>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const texto = document.getElementById('filtroTexto');
    const categoria = document.getElementById('filtroCategoria');
    const critico = document.getElementById('filtroCritico');
    const resultado = document.getElementById('filtroResultado');
    const sinResultados = document.getElementById('filaSinResultados');
    const filas = document.querySelectorAll('.fila-producto');

    // Si no hay productos, no se muestra el filtro y no hay nada que hacer.
    if (!texto || !categoria || !critico) {
        return;
    }

    // Minúsculas y sin tildes, para que "limon" encuentre "Limonero" y "orquidea" a "Orquídea".
    const normalizar = valor => valor
        .toLowerCase()
        .normalize('NFD')
        .replace(/[̀-ͯ]/g, '');

    function filtrar() {
        const buscado = normalizar(texto.value.trim());
        const categoriaElegida = categoria.value;
        const soloCritico = critico.checked;
        let visibles = 0;

        filas.forEach(fila => {
            const coincide =
                normalizar(fila.dataset.texto).includes(buscado) &&
                (categoriaElegida === '' || fila.dataset.categoria === categoriaElegida) &&
                (!soloCritico || fila.dataset.critico === '1');

            // Ocultar la fila sin borrarla: al cambiar el filtro vuelve a aparecer.
            fila.classList.toggle('d-none', !coincide);

            if (coincide) {
                visibles++;
            }
        });

        sinResultados.classList.toggle('d-none', visibles > 0);

        const hayFiltro = buscado !== '' || categoriaElegida !== '' || soloCritico;
        resultado.textContent = hayFiltro
            ? `Mostrando ${visibles} de ${filas.length} productos`
            : '';
    }

    // "input" se dispara con cada letra; "change" al elegir una opción.
    texto.addEventListener('input', filtrar);
    categoria.addEventListener('change', filtrar);
    critico.addEventListener('change', filtrar);
});
</script>
<!-- Los modales se ubican fuera de la tabla para evitar que el navegador reordene el HTML. -->
<?php foreach ($productos as $p): ?>
<div class="modal fade" id="modalEditar<?= (int)$p['id'] ?>" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-lg modal-dialog-centered"><form action="index.php?action=editarproducto" method="POST" enctype="multipart/form-data" class="modal-content"><div class="modal-header"><div><h5 class="modal-title fw-bold">Modificar producto</h5><div class="small text-muted mt-1"><?= htmlspecialchars($p['nombre'], ENT_QUOTES, 'UTF-8') ?></div></div><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button></div><div class="modal-body"><input type="hidden" name="id" value="<?= (int)$p['id'] ?>"><input type="hidden" name="imagen_actual" value="<?= htmlspecialchars($p['imagen'] ?? 'default.jpg', ENT_QUOTES, 'UTF-8') ?>"><div class="row g-3"><div class="col-md-6"><label class="form-label">Categoría</label><select name="categoria" class="form-select" required><?php foreach (['Plantas y Flores','Árboles Frutales','Macetas y Jardineras','Sustratos y Tierra','Herramientas e Insumos'] as $cat): ?><option value="<?= htmlspecialchars($cat, ENT_QUOTES, 'UTF-8') ?>" <?= $p['categoria']===$cat?'selected':'' ?>><?= htmlspecialchars($cat, ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?></select></div><div class="col-md-6"><label class="form-label">Nombre</label><input name="nombre" class="form-control" maxlength="120" value="<?= htmlspecialchars($p['nombre'], ENT_QUOTES, 'UTF-8') ?>" required></div><div class="col-md-4"><label class="form-label">Stock actual</label><input type="number" name="stock" class="form-control" min="0" value="<?= (int)$p['stock'] ?>" required></div><div class="col-md-4"><label class="form-label">Stock mínimo</label><input type="number" name="stock_minimo" class="form-control" min="0" value="<?= (int)$p['stock_minimo'] ?>" required></div><div class="col-md-4"><label class="form-label">Precio ($)</label><input type="number" name="precio" class="form-control" min="0" step="0.01" value="<?= htmlspecialchars((string)$p['precio'], ENT_QUOTES, 'UTF-8') ?>" required></div><div class="col-md-6"><label class="form-label">Ubicación</label><input name="ubicacion" class="form-control" maxlength="120" value="<?= htmlspecialchars($p['ubicacion'] ?? '', ENT_QUOTES, 'UTF-8') ?>"></div><div class="col-md-6"><label class="form-label">Cambiar imagen (opcional)</label><input type="file" name="imagen" class="form-control" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"></div></div></div><div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-success"><i class="bi bi-check2 me-1"></i>Guardar cambios</button></div></form></div></div>
<div class="modal fade" id="modalReponer<?= (int)$p['id'] ?>" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><form action="index.php?action=actualizarstock" method="POST" class="modal-content"><div class="modal-header"><h5 class="modal-title fw-bold">Reponer stock</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><p class="text-muted">Producto: <strong><?= htmlspecialchars($p['nombre'], ENT_QUOTES, 'UTF-8') ?></strong> · Stock actual: <?= (int)$p['stock'] ?></p><input type="hidden" name="id" value="<?= (int)$p['id'] ?>"><input type="hidden" name="tipo" value="reponer"><label class="form-label">Cantidad a ingresar</label><input type="number" name="cantidad" class="form-control" min="1" max="1000000" value="1" required></div><div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-success">Confirmar ingreso</button></div></form></div></div>
<div class="modal fade" id="modalDescontar<?= (int)$p['id'] ?>" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><form action="index.php?action=actualizarstock" method="POST" class="modal-content"><div class="modal-header"><h5 class="modal-title fw-bold">Registrar venta o salida</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><p class="text-muted">Producto: <strong><?= htmlspecialchars($p['nombre'], ENT_QUOTES, 'UTF-8') ?></strong> · Disponible: <?= (int)$p['stock'] ?> unidades</p><input type="hidden" name="id" value="<?= (int)$p['id'] ?>"><input type="hidden" name="tipo" value="descontar"><label class="form-label">Cantidad vendida / retirada</label><input type="number" name="cantidad" class="form-control" min="1" max="<?= max(1,(int)$p['stock']) ?>" value="1" <?= (int)$p['stock'] < 1 ? 'disabled' : 'required' ?>><?php if ((int)$p['stock'] < 1): ?><div class="form-text text-danger">No hay unidades disponibles para vender.</div><?php endif; ?></div><div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-success" <?= (int)$p['stock'] < 1 ? 'disabled' : '' ?>>Confirmar venta</button></div></form></div></div>
<div class="modal fade" id="modalEliminar<?= (int)$p['id'] ?>" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><form action="index.php?action=eliminar" method="POST" class="modal-content"><div class="modal-header"><h5 class="modal-title fw-bold text-danger"><i class="bi bi-trash me-2"></i>Eliminar producto</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body">¿Seguro que querés eliminar <strong><?= htmlspecialchars($p['nombre'], ENT_QUOTES, 'UTF-8') ?></strong>?<p class="text-muted small mt-2 mb-0">Esta acción no se puede deshacer.</p><input type="hidden" name="id" value="<?= (int)$p['id'] ?>"></div><div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-danger">Sí, eliminar</button></div></form></div></div>
<?php endforeach; ?>
<div class="modal fade" id="modalNuevoProducto" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><form action="index.php?action=registrarproducto" method="POST" enctype="multipart/form-data" class="modal-content"><div class="modal-header"><div><h5 class="modal-title fw-bold">Nuevo producto</h5><div class="small text-muted mt-1">Sumá un artículo al inventario.</div></div><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><div class="mb-3"><label class="form-label">Categoría</label><select name="categoria" class="form-select" required><option value="Plantas y Flores">🌿 Plantas y Flores</option><option value="Árboles Frutales">🌳 Árboles Frutales</option><option value="Macetas y Jardineras">🪴 Macetas y Jardineras</option><option value="Sustratos y Tierra">🪵 Sustratos y Tierra</option><option value="Herramientas e Insumos">🛠️ Herramientas e Insumos</option></select></div><div class="mb-3"><label class="form-label">Nombre del producto</label><input name="nombre" class="form-control" maxlength="120" placeholder="Ej. Monstera deliciosa" required></div><div class="row g-3"><div class="col-6"><label class="form-label">Stock inicial</label><input type="number" name="stock" class="form-control" min="0" value="0" required></div><div class="col-6"><label class="form-label">Stock mínimo</label><input type="number" name="stock_minimo" class="form-control" min="0" value="5" required></div><div class="col-6"><label class="form-label">Precio ($)</label><input type="number" name="precio" class="form-control" min="0" step="0.01" placeholder="0,00" required></div><div class="col-6"><label class="form-label">Ubicación</label><input name="ubicacion" class="form-control" maxlength="120" placeholder="Invernadero A"></div><div class="col-12"><label class="form-label">Imagen (opcional)</label><input type="file" name="imagen" class="form-control" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"></div></div></div><div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-success">Guardar producto</button></div></form></div></div>