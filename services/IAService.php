<?php

class IAService
{
    public function llamarIA(string $system, string $pregunta): array
    {
        $apiKey = getenv('GROQ_API_KEY') ?: '';
        $model = getenv('GROQ_MODEL') ?: 'openai/gpt-oss-20b';

        // Verificar la configuración de la API.
        if ($apiKey === '') {
            return [
                'ok' => false,
                'respuesta' => 'La API todavía no está configurada.'
            ];
        }

        // Verificar que cURL esté disponible.
        if (!function_exists('curl_init')) {
            return [
                'ok' => false,
                'respuesta' => 'La extensión cURL no está habilitada.'
            ];
        }

        // Preparar la consulta para Groq.
        $payload = [
            'model' => $model,
            'temperature' => 0.2,
            // Incluye los tokens de razonamiento en los modelos que razonan.
            'max_tokens' => 1000,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => $system
                ],
                [
                    'role' => 'user',
                    'content' => $pregunta
                ]
            ]
        ];

        // Los modelos gpt-oss "piensan" antes de responder y esos tokens se
        // cobran. Para consultas simples alcanza con razonamiento bajo.
        // Solo se envía a esos modelos: otros (como Llama) no lo aceptan.
        if (str_starts_with($model, 'openai/gpt-oss')) {
            $payload['reasoning_effort'] = 'low';
        }

        $json = json_encode(
            $payload,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );

        if ($json === false) {
            return [
                'ok' => false,
                'respuesta' => 'No se pudo preparar la consulta.'
            ];
        }

        // Realizar la petición a Groq.
        $inicio = microtime(true);

        $ch = curl_init(
            'https://api.groq.com/openai/v1/chat/completions'
        );

        // Guardar los encabezados de límites que manda Groq en cada respuesta.
        $limites = [];

        curl_setopt_array($ch, [
            CURLOPT_HEADERFUNCTION => function ($curl, string $linea) use (&$limites): int {
                $partes = explode(':', $linea, 2);

                if (count($partes) === 2) {
                    $nombre = strtolower(trim($partes[0]));

                    if (str_starts_with($nombre, 'x-ratelimit-') || $nombre === 'retry-after') {
                        $limites[$nombre] = trim($partes[1]);
                    }
                }

                return strlen($linea);
            },
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $json,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $apiKey
            ],
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 35
        ]);

        $response = curl_exec($ch);
        $curlError = curl_error($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        $latenciaMs = (int) round((microtime(true) - $inicio) * 1000);

        // Interpretar la respuesta de la API.
        $data = is_string($response)
            ? json_decode($response, true)
            : null;

        // Obtener las métricas de uso informadas por Groq.
        $usage = is_array($data)
            ? ($data['usage'] ?? [])
            : [];

        $tokensEntrada = (int) ($usage['prompt_tokens'] ?? 0);
        $tokensSalida = (int) ($usage['completion_tokens'] ?? 0);
        $tokensTotal = (int) ($usage['total_tokens'] ?? 0);

        // Registrar métricas sin guardar la pregunta ni la clave.
        $registro = [
            'fecha' => date('c'),
            'proveedor' => 'groq',
            'modelo' => $model,
            'http_status' => $status,
            'latencia_ms' => $latenciaMs,
            'tokens_entrada' => $tokensEntrada,
            'tokens_salida' => $tokensSalida,
            'tokens_total' => $tokensTotal,
            // Consultas que quedan hoy y tokens que quedan en este minuto.
            'consultas_restantes_dia' => $limites['x-ratelimit-remaining-requests'] ?? null,
            'tokens_restantes_minuto' => $limites['x-ratelimit-remaining-tokens'] ?? null,
            'reinicio_tokens' => $limites['x-ratelimit-reset-tokens'] ?? null,
            'retry_after' => $limites['retry-after'] ?? null
        ];

        $linea = json_encode(
            $registro,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );

        if ($linea !== false) {
            $rutaLog = sys_get_temp_dir()
                . DIRECTORY_SEPARATOR
                . 'brote_verde_ia.log';

            error_log($linea . PHP_EOL, 3, $rutaLog);
        }

        // Manejar errores de conexión.
        if ($response === false) {
            error_log('Error de conexión cURL: ' . $curlError);

            return [
                'ok' => false,
                'respuesta' => 'No se pudo conectar con la API.'
            ];
        }

        // Manejar errores HTTP de Groq.
        if ($status < 200 || $status >= 300) {
            if ($status === 401 || $status === 403) {
                $mensaje = 'La API rechazó la clave.';
            } elseif ($status === 429) {
                $mensaje = 'Se alcanzó el límite de uso de la API.';

                if (isset($limites['retry-after']) && is_numeric($limites['retry-after'])) {
                    $mensaje .= ' Probá de nuevo en ' . (int) ceil((float) $limites['retry-after']) . ' segundos.';
                }
            } else {
                $mensaje = 'La API respondió con un error HTTP ' . $status . '.';
            }

            error_log(
                'Error de Groq. HTTP ' . $status . ': ' .
                (is_string($response) ? $response : 'Sin respuesta')
            );

            return [
                'ok' => false,
                'respuesta' => $mensaje
            ];
        }

        // Obtener el texto generado por la IA.
        $texto = $data['choices'][0]['message']['content'] ?? null;

        if (!is_string($texto) || trim($texto) === '') {
            error_log('Groq no devolvió contenido válido.');

            return [
                'ok' => false,
                'respuesta' => 'La API no devolvió una respuesta válida.'
            ];
        }

        $texto = trim($texto);

        // Quitar los bloques Markdown si envuelven toda la respuesta.
        $texto = preg_replace(
            '/^```(?:json|php|markdown|text)?[ \t]*\R/i',
            '',
            $texto
        );

        $texto = preg_replace(
            '/\R?[ \t]*```[ \t]*$/',
            '',
            $texto
        );

        $texto = trim($texto);

        // Devolver la respuesta limpia.
        return [
            'ok' => true,
            'respuesta' => $texto
        ];
    }
}