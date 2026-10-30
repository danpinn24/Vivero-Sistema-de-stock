
<?php

class CacheService
{
    private string $directorio;
    private int $duracion = 600; // 10 minutos

    public function __construct()
    {
        $this->directorio = sys_get_temp_dir()
            . DIRECTORY_SEPARATOR
            . 'brote_verde_cache';
    }

    private function obtenerRuta(string $clave): string
    {
        $hash = hash('sha256', $clave);

        return $this->directorio
            . DIRECTORY_SEPARATOR
            . $hash
            . '.json';
    }

    public function obtener(string $clave): ?array
    {
        $ruta = $this->obtenerRuta($clave);

        if (!is_file($ruta)) {
            return null;
        }

        $contenido = file_get_contents($ruta);

        if ($contenido === false) {
            return null;
        }

        $datos = json_decode($contenido, true);

        if (
            !is_array($datos) ||
            !isset($datos['expira'], $datos['respuesta']) ||
            !is_int($datos['expira']) ||
            !is_string($datos['respuesta'])
        ) {
            return null;
        }

        if ($datos['expira'] <= time()) {
            @unlink($ruta);
            return null;
        }

        return [
            'ok' => true,
            'respuesta' => $datos['respuesta'],
            'cache' => true
        ];
    }

    public function guardar(string $clave, string $respuesta): void
    {
        if (!is_dir($this->directorio)) {
            if (
                !mkdir($this->directorio, 0700, true) &&
                !is_dir($this->directorio)
            ) {
                return;
            }
        }

        $ruta = $this->obtenerRuta($clave);

        $datos = [
            'expira' => time() + $this->duracion,
            'respuesta' => $respuesta
        ];

        $contenido = json_encode(
            $datos,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );

        if ($contenido === false) {
            return;
        }

        // Escritura protegida frente a accesos simultáneos.
        $temporal = tempnam($this->directorio, 'cache_');

        if ($temporal === false) {
            return;
        }

        if (file_put_contents($temporal, $contenido, LOCK_EX) === false) {
            @unlink($temporal);
            return;
        }

        if (!@rename($temporal, $ruta)) {
            @unlink($temporal);
        }
    }
}
