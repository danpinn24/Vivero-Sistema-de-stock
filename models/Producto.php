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
