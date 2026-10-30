<?php
// controllers/DashboardController.php
require_once __DIR__ . '/../models/Producto.php';

class DashboardController {
    public function index() {
        $productoModel = new Producto();
        $productos = $productoModel->obtenerTodos();
        $stockBajo = $productoModel->obtenerStockBajo();
        
        // Inicializamos las métricas en 0 por seguridad
        $totalProductos = 0;
        $totalUnidades = 0;
        $valorTotalInventario = 0.0;

        // Si hay productos, calculamos las métricas reales
        if (!empty($productos)) {
            $totalProductos = count($productos);
            $totalUnidades = array_sum(array_column($productos, 'stock'));
            foreach ($productos as $p) {
                $valorTotalInventario += (floatval($p['stock']) * floatval($p['precio']));
            }
        }

        // Cargar las vistas modularizadas
        require_once __DIR__ . '/../views/layouts/header.php';
        require_once __DIR__ . '/../views/dashboard/inicio.php';
        require_once __DIR__ . '/../views/layouts/footer.php';
    }
}