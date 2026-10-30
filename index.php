
<?php

// En producción, los errores deben registrarse en el log y no mostrarse en pantalla.
ini_set('display_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);

// Cargar los controladores.
require_once __DIR__ . '/controllers/DashboardController.php';
require_once __DIR__ . '/controllers/ProductoController.php';
require_once __DIR__ . '/controllers/AsistenteController.php';

// Obtener la acción solicitada.
$action = strtolower($_GET['action'] ?? 'dashboard');

// Crear los controladores.
$productoController = new ProductoController();
$asistenteController = new AsistenteController();

// Dirigir la solicitud a la sección correspondiente.
switch ($action) {
    case 'dashboard':
        (new DashboardController())->index();
        break;

    case 'inventario':
        $productoController->index();
        break;

    case 'registrarproducto':
        $productoController->registrar();
        break;

    case 'actualizarstock':
        $productoController->actualizarStock();
        break;

    case 'editarproducto':
        $productoController->editar();
        break;

    case 'eliminar':
        $productoController->eliminar();
        break;

    case 'asistente':
        $asistenteController->index();
        break;

    case 'consultar_ia':
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);

            echo json_encode([
                'ok' => false,
                'respuesta' => 'Método no permitido.'
            ], JSON_UNESCAPED_UNICODE);

            break;
        }

        $input = json_decode(
            file_get_contents('php://input'),
            true
        );

        $prompt = trim((string) ($input['prompt'] ?? ''));

        if ($prompt === '') {
            http_response_code(400);

            echo json_encode([
                'ok' => false,
                'respuesta' => 'Escribí una consulta para continuar.'
            ], JSON_UNESCAPED_UNICODE);

            break;
        }

        if (strlen($prompt) > 4000) {
            http_response_code(413);

            echo json_encode([
                'ok' => false,
                'respuesta' => 'La consulta es demasiado larga.'
            ], JSON_UNESCAPED_UNICODE);

            break;
        }

        try {
            $resultado = $asistenteController->consultarAsistente($prompt);

            if (!($resultado['ok'] ?? false)) {
                http_response_code(502);
            }

            echo json_encode(
                $resultado,
                JSON_UNESCAPED_UNICODE
            );

        } catch (Throwable $e) {
            error_log('Error asistente: ' . $e->getMessage());

            http_response_code(500);

            echo json_encode([
                'ok' => false,
                'respuesta' => 'Ocurrió un error interno al consultar el asistente.'
            ], JSON_UNESCAPED_UNICODE);
        }

        break;

    default:
        http_response_code(404);

        echo '<div class="container py-5">';
        echo '<h1>Página no encontrada</h1>';
        echo '<a href="index.php">Volver al inicio</a>';
        echo '</div>';

        break;
}
