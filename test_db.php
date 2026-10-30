<?php
require_once 'config/database.php';

$db = Database::getInstance();
echo "¡Conexión exitosa y tabla 'productos' verificada/creada!";