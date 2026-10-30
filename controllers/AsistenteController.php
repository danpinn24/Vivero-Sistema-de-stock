<?php

require_once __DIR__ . '/../models/Producto.php';
require_once __DIR__ . '/../services/IAService.php';
require_once __DIR__ . '/../services/CacheService.php';

class AsistenteController
{
    public function index(): void
    {
        require __DIR__ . '/../views/layouts/header.php';
        require __DIR__ . '/../views/asistente/chat.php';
        require __DIR__ . '/../views/layouts/footer.php';
    }

    public function consultarAsistente(string $pregunta): array
    {
        // Leer variables del archivo .env local.
        $envPath = __DIR__ . '/../.env';

        if (is_file($envPath) && is_readable($envPath)) {
            foreach (
                file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES)
                as $line
            ) {
                $line = trim($line);

                if (
                    $line === '' ||
                    str_starts_with($line, '#') ||
                    !str_contains($line, '=')
                ) {
                    continue;
                }

                [$key, $value] = array_map(
                    'trim',
                    explode('=', $line, 2)
                );

                if ($key !== '' && getenv($key) === false) {
                    putenv($key . '=' . $value);
                }
            }
        }

        // Validar la consulta recibida.
        $pregunta = trim($pregunta);

        if ($pregunta === '' || mb_strlen($pregunta) > 1000) {
            return [
                'ok' => false,
                'respuesta' => 'La consulta debe tener entre 1 y 1000 caracteres.'
            ];
        }

        // Obtener el inventario actual.
        $productos = (new Producto())->obtenerResumenParaAsistente();

        $inventario = json_encode(
            $productos,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );

        if ($inventario === false) {
            return [
                'ok' => false,
                'respuesta' => 'No se pudo preparar el inventario.'
            ];
        }

        // Preparar las instrucciones para la IA.
        $system = 'Sos el asistente del Vivero Brote Verde.
Respondé en español rioplatense, de forma clara y breve.
Contestá exclusivamente con la información del inventario JSON.
No inventes productos, cantidades, precios ni ubicaciones.
Si la información no alcanza, decilo.
Para stock crítico, considerá stock menor o igual a stock_minimo.
Los precios están expresados en pesos argentinos.

Inventario actual (JSON):
' . $inventario;

        // Identificar el modelo utilizado.
        $modelo = getenv('GROQ_MODEL')
            ?: 'llama-3.3-70b-versatile';

        // Crear una clave según el modelo, el inventario y la pregunta.
        $claveCache = hash(
            'sha256',
            $modelo . "\n" . $system . "\n" . $pregunta
        );

        // Mensaje para comprobar que se ejecuta este controlador.
        error_log('[PRUEBA] Entro al controlador del asistente');

        $cache = new CacheService();

        // Buscar una respuesta guardada.
        $respuestaGuardada = $cache->obtener($claveCache);

        if ($respuestaGuardada !== null) {
            error_log('[CACHE HIT] Respuesta recuperada de caché');

            return $respuestaGuardada;
        }

        // Si no existe una respuesta guardada, consultar a Groq.
        error_log('[CACHE MISS] Se consultará a Groq');

        $ia = new IAService();

        $resultado = $ia->llamarIA($system, $pregunta);

        // Guardar únicamente las respuestas correctas.
        if (
            ($resultado['ok'] ?? false) === true &&
            isset($resultado['respuesta']) &&
            is_string($resultado['respuesta'])
        ) {
            $cache->guardar(
                $claveCache,
                $resultado['respuesta']
            );
        }

        return $resultado;
    }
}
