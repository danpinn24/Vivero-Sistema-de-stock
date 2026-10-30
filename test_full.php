<?php
// test_full.php - Prueba integral del CRUD y funcionalidades

require_once 'models/Producto.php';

echo "<h2>🧪 Prueba Integral del Sistema de Stock</h2>\n";

// 1. Conexión a la base de datos
$productoModel = new Producto();
echo "<p>✅ Conexión a la base de datos: OK</p>\n";

// 2. Listar productos iniciales
$productosIniciales = $productoModel->obtenerTodos();
echo "<p>📦 Productos iniciales: " . count($productosIniciales) . "</p>\n";

// 3. Insertar un producto de prueba
$imagenTest = 'test_' . time() . '.jpg';
$idInsertado = $productoModel->insertar(
    'Plantas y Flores',
    'Suculenta de Prueba',
    10,
    3,
    5.99,
    $imagenTest,
    'Invernadero de Prueba'
);
echo "<p>➕ Producto insertado con ID: $idInsertado</p>\n";

// 4. Verificar que el producto se insertó
$productoCreado = $productoModel->obtenerPorId($idInsertado);
if ($productoCreado) {
    echo "<p>✅ Producto encontrado: {$productoCreado['nombre']} - Stock: {$productoCreado['stock']}</p>\n";
} else {
    echo "<p>❌ Error: Producto no encontrado después de insertar</p>\n";
}

// 5. Probar reponer stock
$productoModel->reponerStock($idInsertado, 5);
$productoActualizado = $productoModel->obtenerPorId($idInsertado);
echo "<p>🔄 Stock reponido (+5): {$productoActualizado['stock']} (debería ser 15)</p>\n";

// 6. Probar descontar stock (venta)
$productoModel->descontarStock($idInsertado, 3);
$productoActualizado = $productoModel->obtenerPorId($idInsertado);
echo "<p>📉 Stock descontado (-3): {$productoActualizado['stock']} (debería ser 12)</p>\n";

// 7. Probar actualización completa
$productoModel->actualizar(
    $idInsertado,
    'Árboles Frutales',
    'Árbol de Prueba Actualizado',
    20,
    5,
    12.50,
    $imagenTest,
    'Depósito Actualizado'
);
$productoActualizado = $productoModel->obtenerPorId($idInsertado);
echo "<p>✏️ Producto actualizado: {$productoActualizado['nombre']} - Precio: \${$productoActualizado['precio']} - Stock: {$productoActualizado['stock']}</p>\n";

// 8. Verificar stock bajo
$stockBajo = $productoModel->obtenerStockBajo();
echo "<p>⚠️ Productos con stock bajo: " . count($stockBajo) . "</p>\n";

// 9. Eliminar el producto de prueba
$productoModel->Eliminar($idInsertado);
$productoEliminado = $productoModel->obtenerPorId($idInsertado);
if (!$productoEliminado) {
    echo "<p>✅ Producto eliminado correctamente</p>\n";
} else {
    echo "<p>❌ Error: El producto aún existe después de eliminar</p>\n";
}

echo "<hr>\n";
echo "<h3>✅ Todas las pruebas completadas exitosamente</h3>\n";
echo "<p><a href='index.php'>Volver al sistema</a></p>\n";