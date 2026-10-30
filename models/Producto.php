<?php
require_once __DIR__ . '/../config/database.php';

class Producto {
    private PDO $db;
    public function __construct() { $this->db = Database::getInstance(); }
    public function obtenerTodos(): array {
        return $this->db->query('SELECT * FROM productos ORDER BY id DESC')->fetchAll();
    }
    public function obtenerStockBajo(): array {
        return $this->db->query('SELECT * FROM productos WHERE stock <= stock_minimo ORDER BY stock ASC')->fetchAll();
    }
    public function obtenerResumenParaAsistente(): array {
        return $this->db->query('SELECT id, categoria, nombre, stock, stock_minimo, precio, ubicacion FROM productos ORDER BY categoria, nombre')->fetchAll();
    }
    // --- Consultas usadas por el asistente para enviar solo lo necesario ---
    public function obtenerEstadisticas(): array {
        $totales = $this->db->query('SELECT COUNT(*) AS total_productos, COALESCE(SUM(stock),0) AS total_unidades, COALESCE(SUM(stock*precio),0) AS valor_total, COALESCE(SUM(stock <= stock_minimo),0) AS stock_critico, COALESCE(SUM(stock = 0),0) AS sin_stock FROM productos')->fetch();
        $totales['por_categoria'] = $this->db->query('SELECT categoria, COUNT(*) AS productos, SUM(stock) AS unidades FROM productos GROUP BY categoria ORDER BY categoria')->fetchAll();
        return $totales;
    }
    public function obtenerSinStock(): array {
        return $this->db->query('SELECT id, categoria, nombre, stock, stock_minimo, precio, ubicacion FROM productos WHERE stock = 0 ORDER BY nombre')->fetchAll();
    }
    public function obtenerPorCategoria(string $categoria): array {
        $stmt=$this->db->prepare('SELECT id, categoria, nombre, stock, stock_minimo, precio, ubicacion FROM productos WHERE categoria = ? ORDER BY nombre');
        $stmt->execute([$categoria]); return $stmt->fetchAll();
    }
    public function obtenerPorPrecio(string $orden, int $limite): array {
        $orden = $orden === 'ASC' ? 'ASC' : 'DESC'; // solo dos valores posibles: evita inyección SQL
        $stmt=$this->db->prepare("SELECT id, categoria, nombre, stock, stock_minimo, precio, ubicacion FROM productos ORDER BY precio $orden LIMIT ?");
        $stmt->bindValue(1, $limite, PDO::PARAM_INT); $stmt->execute(); return $stmt->fetchAll();
    }
    public function insertar($categoria,$nombre,$stock,$stock_minimo,$precio,$imagen,$ubicacion): bool {
        $sql='INSERT INTO productos (categoria,nombre,stock,stock_minimo,precio,imagen,ubicacion) VALUES (?,?,?,?,?,?,?)';
        return $this->db->prepare($sql)->execute([$categoria,$nombre,$stock,$stock_minimo,$precio,$imagen,$ubicacion]);
    }
    public function reponerStock($id,$cantidad): bool {
        $stmt=$this->db->prepare('UPDATE productos SET stock = stock + ? WHERE id = ?');
        $stmt->execute([$cantidad,$id]); return $stmt->rowCount() > 0;
    }
    public function descontarStock($id,$cantidad): bool {
        // La condición stock >= cantidad evita ventas por encima de las existencias.
        $stmt=$this->db->prepare('UPDATE productos SET stock = stock - ? WHERE id = ? AND stock >= ?');
        $stmt->execute([$cantidad,$id,$cantidad]); return $stmt->rowCount() > 0;
    }
    public function actualizar($id,$categoria,$nombre,$stock,$stock_minimo,$precio,$imagen,$ubicacion): bool {
        $sql='UPDATE productos SET categoria=?, nombre=?, stock=?, stock_minimo=?, precio=?, imagen=?, ubicacion=? WHERE id=?';
        $stmt=$this->db->prepare($sql); $stmt->execute([$categoria,$nombre,$stock,$stock_minimo,$precio,$imagen,$ubicacion,$id]);
        return $stmt->rowCount() > 0 || $this->obtenerPorId($id) !== false;
    }
    public function eliminar($id): bool {
        $stmt=$this->db->prepare('DELETE FROM productos WHERE id=?'); $stmt->execute([$id]); return $stmt->rowCount() > 0;
    }
    public function obtenerPorId($id) {
        $stmt=$this->db->prepare('SELECT * FROM productos WHERE id=?'); $stmt->execute([$id]); return $stmt->fetch();
    }
}