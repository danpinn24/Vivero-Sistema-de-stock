<?php
// config/database.php

class Database {
    private static $instance = null;
    private $pdo;

    private function __construct() {
        try {
            // Ruta del archivo SQLite en el directorio raíz
            $dbPath = __DIR__ . '/../vivero_brote_verde.db';
            
            $this->pdo = new PDO("sqlite:" . $dbPath);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

            // Crear tabla de productos si no existe
            $this->crearTablas();

        } catch (PDOException $e) {
            die("Error de conexión a la base de datos: " . $e->getMessage());
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance->pdo;
    }

    private function crearTablas() {
        $sql = "CREATE TABLE IF NOT EXISTS productos (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            categoria TEXT NOT NULL,
            nombre TEXT NOT NULL,
            stock INTEGER NOT NULL DEFAULT 0,
            stock_minimo INTEGER NOT NULL DEFAULT 5,
            precio REAL NOT NULL DEFAULT 0.0,
            imagen TEXT DEFAULT 'default.jpg',
            ubicacion TEXT DEFAULT 'Depósito General',
            creado_en DATETIME DEFAULT CURRENT_TIMESTAMP
        );";

        $this->pdo->exec($sql);
    }
}