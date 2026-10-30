# Vivero Brote Verde

Aplicación PHP MVC con SQLite, Bootstrap y asistente de inventario mediante API remota de Groq.

## Requisitos

- PHP 8.0 o superior.
- Extensiones PHP `pdo_sqlite`, `fileinfo` y `curl` (esta última es necesaria para el asistente remoto).
- Apache/XAMPP o un servidor PHP local.
- Conexión a Internet para Bootstrap y para el asistente IA.

## Ejecutar en XAMPP

1. Copiá la carpeta `Sistema de stock` dentro de `C:\xampp\htdocs\`.
2. Abrí XAMPP y encendé Apache.
3. Visitá `http://localhost/Sistema%20de%20stock/index.php`.
4. Si la base de datos no se abre, revisá que `pdo_sqlite` esté habilitado en el `php.ini` de Apache y reiniciá Apache.

La base existente está en `vivero_brote_verde.db`. Hacé una copia de ese archivo antes de probar cambios o borrar productos.

## Configurar el asistente remoto

1. Creá una cuenta y obtené una API key en https://console.groq.com/.
2. Copiá `.env.example` a un archivo llamado `.env` en la raíz del proyecto.
3. Reemplazá `pegá_tu_clave_acá` por tu clave real. No compartas ese archivo ni publiques la clave.
4. Podés cambiar `GROQ_MODEL` por un modelo disponible en tu cuenta.
5. Verificá que la extensión `curl` esté habilitada para PHP/Apache y reiniciá Apache.

El archivo `.env` está incluido en `.gitignore`; la aplicación lo lee localmente. El plan gratuito está sujeto a los límites y condiciones vigentes de Groq. Si la API devuelve un límite de uso, el chat mostrará un mensaje.

## Cambios principales

- Modales del inventario ubicados fuera de la tabla para evitar problemas del HTML con el DOM y Bootstrap.
- Botones visibles para modificar, reponer stock, registrar ventas y eliminar productos.
- Validaciones de stock y precios; una venta no puede superar las existencias disponibles.
- Mensajes de resultado en inventario y respuestas JSON consistentes en el chat.
- El asistente recibe una lectura del inventario desde SQLite antes de generar la respuesta.
- Diseño adaptable a móviles con navegación lateral, tarjetas, tabla y chat rediseñados.

## Nota de seguridad

Este proyecto académico no incorpora autenticación de usuarios. No lo publiques directamente en Internet sin agregar autenticación, autorización, protección CSRF y configuración de producción.
