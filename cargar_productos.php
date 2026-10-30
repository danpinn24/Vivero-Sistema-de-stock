<?php
// cargar_productos.php - Carga productos de ejemplo para probar el asistente.
// Uso: desde la carpeta del proyecto, ejecutar "php cargar_productos.php".
// No duplica productos: si ya existe uno con el mismo nombre, lo saltea.
//
// Imágenes (opcional): poné las fotos en la carpeta "imagenes_carga/" con el
// nombre del producto en minúsculas, sin tildes y con guiones. Ejemplos:
//   Rosa roja        -> imagenes_carga/rosa-roja.jpg
//   Tierra fértil 25 L -> imagenes_carga/tierra-fertil-25-l.png
// Formatos: jpg, jpeg, png o webp. Si volvés a ejecutar el script después de
// agregar fotos, se las asigna a los productos que todavía no tienen imagen.

require_once __DIR__ . '/models/Producto.php';

const CARPETA_FOTOS = __DIR__ . '/imagenes_carga/';
const CARPETA_UPLOADS = __DIR__ . '/assets/img/uploads/';

// "Tierra fértil 25 L" -> "tierra-fertil-25-l"
function slug(string $texto): string
{
    $texto = mb_strtolower($texto, 'UTF-8');
    $texto = strtr($texto, ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n']);
    return trim(preg_replace('/[^a-z0-9]+/', '-', $texto), '-');
}

// Busca la foto del producto y la copia a uploads con un nombre aleatorio,
// igual que hace el formulario. Devuelve el nombre nuevo o null si no hay foto.
function copiarFoto(string $nombre): ?string
{
    foreach (['jpg', 'jpeg', 'png', 'webp'] as $ext) {
        $origen = CARPETA_FOTOS . slug($nombre) . '.' . $ext;

        if (is_file($origen)) {
            if (!is_dir(CARPETA_UPLOADS)) {
                mkdir(CARPETA_UPLOADS, 0755, true);
            }
            $destino = bin2hex(random_bytes(16)) . '.' . ($ext === 'jpeg' ? 'jpg' : $ext);
            return copy($origen, CARPETA_UPLOADS . $destino) ? $destino : null;
        }
    }

    return null;
}

// categoria, nombre, stock, stock_minimo, precio, ubicacion
$productos = [
    // Plantas y Flores (algunas con stock bajo o en cero para probar alertas)
    ['Plantas y Flores', 'Rosa roja', 24, 8, 4500, 'Invernadero 1'],
    ['Plantas y Flores', 'Rosa blanca', 6, 8, 4500, 'Invernadero 1'],
    ['Plantas y Flores', 'Lavanda', 30, 10, 3800, 'Sector exterior'],
    ['Plantas y Flores', 'Romero', 18, 6, 2900, 'Sector exterior'],
    ['Plantas y Flores', 'Potus', 15, 5, 5200, 'Invernadero 2'],
    ['Plantas y Flores', 'Monstera deliciosa', 4, 5, 18500, 'Invernadero 2'],
    ['Plantas y Flores', 'Ficus lyrata', 3, 3, 24000, 'Exhibición'],
    ['Plantas y Flores', 'Helecho serrucho', 12, 5, 6800, 'Invernadero 2'],
    ['Plantas y Flores', 'Orquídea phalaenopsis', 7, 4, 21000, 'Exhibición'],
    ['Plantas y Flores', 'Suculenta echeveria', 40, 15, 1800, 'Exhibición'],
    ['Plantas y Flores', 'Cactus mammillaria', 22, 10, 2200, 'Exhibición'],
    ['Plantas y Flores', 'Aloe vera', 16, 6, 3500, 'Exhibición'],
    ['Plantas y Flores', 'Jazmín del país', 9, 6, 7400, 'Sector exterior'],
    ['Plantas y Flores', 'Malvón', 0, 6, 3200, 'Sector exterior'],
    ['Plantas y Flores', 'Petunia', 50, 20, 1200, 'Sector exterior'],
    ['Plantas y Flores', 'Margarita', 28, 10, 1900, 'Sector exterior'],
    ['Plantas y Flores', 'Begonia', 11, 6, 3900, 'Invernadero 1'],
    ['Plantas y Flores', 'Gerbera', 5, 8, 4100, 'Invernadero 1'],
    ['Plantas y Flores', 'Sansevieria', 14, 5, 8900, 'Invernadero 2'],
    ['Plantas y Flores', 'Calathea', 6, 4, 12500, 'Invernadero 2'],
    ['Plantas y Flores', 'Albahaca', 25, 10, 1500, 'Sector exterior'],
    ['Plantas y Flores', 'Menta', 2, 8, 1500, 'Sector exterior'],
    // Árboles Frutales
    ['Árboles Frutales', 'Limonero', 8, 3, 32000, 'Sector exterior'],
    ['Árboles Frutales', 'Naranjo', 5, 3, 32000, 'Sector exterior'],
    ['Árboles Frutales', 'Durazno', 2, 3, 28000, 'Sector exterior'],
    ['Árboles Frutales', 'Higuera', 4, 2, 26000, 'Sector exterior'],
    // Macetas y Jardineras
    ['Macetas y Jardineras', 'Maceta plástica 20 cm', 60, 20, 1600, 'Depósito General'],
    ['Macetas y Jardineras', 'Maceta de barro 30 cm', 18, 10, 5400, 'Depósito General'],
    ['Macetas y Jardineras', 'Jardinera 60 cm', 9, 5, 7800, 'Depósito General'],
    ['Macetas y Jardineras', 'Maceta colgante', 3, 6, 4300, 'Exhibición'],
    // Sustratos y Tierra
    ['Sustratos y Tierra', 'Tierra fértil 25 L', 35, 15, 4900, 'Depósito General'],
    ['Sustratos y Tierra', 'Compost 10 L', 20, 10, 3600, 'Depósito General'],
    ['Sustratos y Tierra', 'Sustrato para cactus 5 L', 7, 8, 3100, 'Depósito General'],
    ['Sustratos y Tierra', 'Perlita 5 L', 12, 5, 4200, 'Depósito General'],
    // Herramientas e Insumos
    ['Herramientas e Insumos', 'Regadera 5 L', 10, 4, 6900, 'Depósito General'],
    ['Herramientas e Insumos', 'Manguera 15 m', 6, 3, 15500, 'Depósito General'],
    ['Herramientas e Insumos', 'Tijera de podar', 8, 4, 11200, 'Exhibición'],
    ['Herramientas e Insumos', 'Guantes de jardín', 20, 8, 3800, 'Exhibición'],
    ['Herramientas e Insumos', 'Fertilizante NPK 1 kg', 4, 6, 8700, 'Depósito General'],
    ['Herramientas e Insumos', 'Pulverizador 1 L', 13, 5, 3300, 'Depósito General'],
];

$modelo = new Producto();
$db = Database::getInstance();

// Productos que ya existen, por nombre en minúsculas, para no duplicar.
$existentes = [];
foreach ($modelo->obtenerTodos() as $p) {
    $existentes[mb_strtolower($p['nombre'])] = $p;
}

$cargados = 0;
$salteados = 0;
$conFoto = 0;
$sinFoto = [];

foreach ($productos as [$categoria, $nombre, $stock, $minimo, $precio, $ubicacion]) {
    $clave = mb_strtolower($nombre);

    if (isset($existentes[$clave])) {
        $salteados++;
        $actual = $existentes[$clave];

        // Si ya existía sin imagen y ahora hay una foto, se la asigna.
        if (($actual['imagen'] ?? 'default.jpg') === 'default.jpg') {
            $foto = copiarFoto($nombre);
            if ($foto !== null) {
                $db->prepare('UPDATE productos SET imagen = ? WHERE id = ?')->execute([$foto, $actual['id']]);
                $conFoto++;
            } else {
                $sinFoto[] = slug($nombre);
            }
        }
        continue;
    }

    $foto = copiarFoto($nombre);
    if ($foto !== null) {
        $conFoto++;
    } else {
        $sinFoto[] = slug($nombre);
    }

    $modelo->insertar($categoria, $nombre, $stock, $minimo, $precio, $foto ?? 'default.jpg', $ubicacion);
    $cargados++;
}

echo "Productos cargados: $cargados\n";
echo "Ya existían (salteados): $salteados\n";
echo "Imágenes asignadas: $conFoto\n";
echo 'Total en la base: ' . count($modelo->obtenerTodos()) . "\n";

if ($sinFoto !== []) {
    echo "\nSin foto (nombres de archivo esperados en imagenes_carga/, .jpg/.png/.webp):\n";
    foreach ($sinFoto as $s) {
        echo "  $s\n";
    }
}