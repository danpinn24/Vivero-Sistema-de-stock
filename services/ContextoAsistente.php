<?php

require_once __DIR__ . '/../models/Producto.php';


class ContextoAsistente
{
    // Máximo de productos que se envían a la IA en una consulta.
    private const MAX_FILAS = 25;

    // Máximo de productos en la lista de respaldo (solo 4 datos por producto).
    private const MAX_RESPALDO = 150;

    // Palabras que no sirven para buscar productos por nombre.
    private const PALABRAS_VACIAS = [
        'que', 'cual', 'cuales', 'cuanto', 'cuantos', 'cuanta', 'cuantas',
        'hay', 'tengo', 'tenemos', 'queda', 'quedan', 'para', 'por', 'con',
        'del', 'las', 'los', 'una', 'uno', 'unos', 'unas', 'esta', 'estan',
        'donde', 'como', 'cuando', 'stock', 'precio', 'precios', 'producto',
        'productos', 'vivero', 'tiene', 'tienen', 'sobre', 'decime', 'dame',
        'mostrame', 'necesito', 'quiero', 'saber', 'todos', 'todas', 'mas',
        'menos', 'entre', 'cada', 'algun', 'alguna', 'sus', 'este', 'esos',
        'esas', 'ese', 'esa', 'son', 'ser', 'hola', 'gracias', 'favor',
        'estas', 'estoy', 'buenas', 'buenos', 'dias', 'tardes', 'noches',
    ];

    // Sinónimos simples para detectar categorías en la pregunta.
    private const CATEGORIAS = [
        'Plantas y Flores'       => ['planta', 'plantas', 'flor', 'flores'],
        'Árboles Frutales'       => ['arbol', 'arboles', 'frutal', 'frutales'],
        'Macetas y Jardineras'   => ['maceta', 'macetas', 'jardinera', 'jardineras'],
        'Sustratos y Tierra'     => ['sustrato', 'sustratos', 'tierra', 'compost', 'abono'],
        'Herramientas e Insumos' => ['herramienta', 'herramientas', 'insumo', 'insumos'],
    ];

    private Producto $producto;

    public function __construct(?Producto $producto = null)
    {
        $this->producto = $producto ?? new Producto();
    }

    /**
     * Devuelve el texto con los datos relevantes para la pregunta.
     */
    public function construir(string $pregunta): string
    {
        $texto = $this->normalizar($pregunta);
        $partes = [];
        $filas = [];

        // 1. Resumen general: siempre se envía porque es muy corto.
        $partes[] = $this->resumenGeneral();

        // 2. Stock bajo o crítico.
        if ($this->contiene($texto, ['bajo', 'critico', 'agot', 'reponer', 'repos', 'falta', 'minimo', 'poco', 'alerta', 'comprar'])) {
            $filas = array_merge($filas, $this->producto->obtenerStockBajo());
        }

        // 3. Productos sin stock.
        if ($this->contiene($texto, ['sin stock', 'cero', 'no hay', 'no queda'])) {
            $filas = array_merge($filas, $this->producto->obtenerSinStock());
        }

        // 4. Precios: los más caros o los más baratos.
        if ($this->contiene($texto, ['caro', 'costoso'])) {
            $filas = array_merge($filas, $this->producto->obtenerPorPrecio('DESC', 5));
        }
        if ($this->contiene($texto, ['barato', 'economico'])) {
            $filas = array_merge($filas, $this->producto->obtenerPorPrecio('ASC', 5));
        }

        // 5. Categorías mencionadas.
        foreach (self::CATEGORIAS as $categoria => $claves) {
            if ($this->contienePalabra($texto, $claves)) {
                $filas = array_merge($filas, $this->producto->obtenerPorCategoria($categoria));
            }
        }

        // 6. Productos mencionados por nombre.
        $filas = array_merge($filas, $this->buscarPorNombre($texto));

        // Quitar repetidos y limitar la cantidad.
        $filas = $this->sinRepetidos($filas);
        $recortado = count($filas) > self::MAX_FILAS;
        $filas = array_slice($filas, 0, self::MAX_FILAS);

        if ($filas !== []) {
            // Indicar la cantidad ayuda a que la IA no omita filas al listarlas.
            $partes[] = 'Productos relevantes: ' . count($filas) . ' en total. Si la respuesta los lista, incluí los ' . count($filas) . ".\n"
                . "(id|nombre|categoria|stock|stock_minimo|precio|ubicacion)\n"
                . $this->formatoCompacto($filas);

            if ($recortado) {
                $partes[] = 'Nota: hay más productos que coinciden; se muestran los primeros ' . self::MAX_FILAS . '.';
            }
        } elseif ($this->contiene($texto, ['total', 'vale', 'valor', 'resumen', 'unidades', 'categoria'])
            || $this->palabrasDeBusqueda($texto) === []) {
            // Preguntas generales o saludos: el resumen ya alcanza.
            $partes[] = 'Para esta pregunta alcanza con el resumen. Si piden un producto puntual, sugerí nombrarlo.';
        } else {
            // Respaldo: ninguna regla coincidió. Se manda una lista corta de
            // todos los productos para que la IA deduzca cuáles sirven.
            $partes[] = $this->listaDeRespaldo();
        }

        return implode("\n\n", $partes);
    }

    /**
     * Totales del inventario y conteo por categoría, calculados en SQL.
     */
    private function resumenGeneral(): string
    {
        $e = $this->producto->obtenerEstadisticas();

        $lineas = [
            'Resumen del inventario:',
            'productos=' . $e['total_productos']
                . ' unidades=' . $e['total_unidades']
                . ' valor_total=$' . number_format((float) $e['valor_total'], 2, ',', '.')
                . ' stock_critico=' . $e['stock_critico']
                . ' sin_stock=' . $e['sin_stock'],
            'Por categoría (categoria|productos|unidades):',
        ];

        foreach ($e['por_categoria'] as $c) {
            $lineas[] = $c['categoria'] . '|' . $c['productos'] . '|' . $c['unidades'];
        }

        return implode("\n", $lineas);
    }

    /**
     * Lista breve de productos (id|nombre|stock|precio) para cuando la
     * pregunta no coincide con ninguna regla. Sigue siendo mucho más
     * barata que el inventario completo en JSON.
     */
    private function listaDeRespaldo(): string
    {
        $todos = $this->producto->obtenerResumenParaAsistente();
        $lineas = [];

        foreach (array_slice($todos, 0, self::MAX_RESPALDO) as $f) {
            $lineas[] = $f['id'] . '|' . $f['nombre'] . '|' . $f['stock'] . '|' . $f['precio'];
        }

        $texto = "La pregunta no coincidió con ninguna búsqueda específica.\n"
            . "Lista de productos (id|nombre|stock|precio). Usá solo los que tengan relación con la pregunta:\n"
            . implode("\n", $lineas);

        if (count($todos) > self::MAX_RESPALDO) {
            $texto .= "\nNota: la lista está recortada a " . self::MAX_RESPALDO
                . ' productos. Si no encontrás lo que piden, sugerí nombrar el producto o la categoría.';
        }

        return $texto;
    }

    /**
     * Busca productos cuyo nombre contenga alguna palabra de la pregunta.
     * La comparación se hace en PHP para ignorar mayúsculas y tildes.
     */
    private function buscarPorNombre(string $texto): array
    {
        $palabras = $this->palabrasDeBusqueda($texto);

        if ($palabras === []) {
            return [];
        }

        $encontrados = [];

        foreach ($this->producto->obtenerResumenParaAsistente() as $fila) {
            $nombre = $this->normalizar($fila['nombre']);

            foreach ($palabras as $palabra) {
                // Se recorta la "s" final para que "rosas" encuentre "Rosa".
                $raiz = mb_strlen($palabra) > 4 ? rtrim($palabra, 's') : $palabra;

                if (str_contains($nombre, $raiz)) {
                    $encontrados[] = $fila;
                    break;
                }
            }
        }

        return $encontrados;
    }

    /**
     * Palabras de la pregunta que sirven para buscar (sin las palabras vacías).
     */
    private function palabrasDeBusqueda(string $texto): array
    {
        return array_values(array_filter(
            preg_split('/[^a-z0-9ñ]+/u', $texto) ?: [],
            fn ($p) => mb_strlen($p) >= 3 && !in_array($p, self::PALABRAS_VACIAS, true)
        ));
    }

    /**
     * Una línea por producto con los valores separados por "|".
     * Gasta muchos menos tokens que JSON porque no repite los nombres de campo.
     */
    private function formatoCompacto(array $filas): string
    {
        $lineas = [];

        foreach ($filas as $f) {
            $lineas[] = implode('|', [
                $f['id'],
                $f['nombre'],
                $f['categoria'],
                $f['stock'],
                $f['stock_minimo'],
                $f['precio'],
                $f['ubicacion'],
            ]);
        }

        return implode("\n", $lineas);
    }

    private function sinRepetidos(array $filas): array
    {
        $unicos = [];

        foreach ($filas as $f) {
            $unicos[$f['id']] = $f;
        }

        return array_values($unicos);
    }

    /**
     * Pasa a minúsculas y quita tildes para comparar textos.
     */
    private function normalizar(string $texto): string
    {
        $texto = mb_strtolower($texto, 'UTF-8');

        return strtr($texto, [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u',
        ]);
    }

    /** Busca fragmentos dentro del texto (sirve para raíces como "agot"). */
    private function contiene(string $texto, array $fragmentos): bool
    {
        foreach ($fragmentos as $f) {
            if (str_contains($texto, $f)) {
                return true;
            }
        }

        return false;
    }

    /** Busca palabras completas, para no confundir "flor" con "florero". */
    private function contienePalabra(string $texto, array $palabras): bool
    {
        foreach ($palabras as $p) {
            if (preg_match('/\b' . preg_quote($p, '/') . '\b/u', $texto)) {
                return true;
            }
        }

        return false;
    }
}