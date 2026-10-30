<div class="page-heading">
    <div>
        <h1>
            <i class="bi bi-chat-square-heart me-2" style="color:var(--green)"></i>
            Asistente inteligente
        </h1>
        <p>Consultá existencias, precios y ubicaciones del inventario.</p>
    </div>

    <span class="status-pill">
        <span></span>
        Consulta con datos del vivero
    </span>
</div>

<div class="chat-panel">
    <div class="card mb-3">
        <div class="card-header px-4 py-3 d-flex align-items-center gap-3">
            <div class="brand-icon">
                <i class="bi bi-flower1"></i>
            </div>

            <div>
                <strong>Tu asistente de Brote Verde</strong>
                <div class="text-muted small mt-1">
                    Respuestas basadas en el stock registrado
                </div>
            </div>
        </div>

        <div class="chat-messages" id="chatContainer" aria-live="polite">
            <div class="chat-message">
                <div class="chat-avatar">
                    <i class="bi bi-robot"></i>
                </div>

                <div class="chat-bubble">
                    ¡Hola! 🌱 Soy el asistente de Brote Verde. Podés preguntarme
                    qué productos tenemos, cuáles están por agotarse, sus precios
                    o dónde están ubicados.
                </div>
            </div>
        </div>

        <div class="chat-input-area">
            <form id="formChat" class="d-flex gap-2">
                <input
                    type="text"
                    id="inputPregunta"
                    class="form-control"
                    maxlength="1000"
                    placeholder="Escribí tu consulta sobre el inventario…"
                    autocomplete="off"
                    required
                >

                <button
                    class="btn btn-success text-nowrap"
                    type="submit"
                    id="btnEnviar"
                >
                    <i class="bi bi-send-fill me-1"></i>
                    Consultar
                </button>
            </form>

            <div class="small text-muted mt-2">
                <i class="bi bi-shield-check me-1"></i>
                La consulta se procesa en la API remota; no incluyas datos personales.
            </div>
        </div>
    </div>

    <div class="card p-3 p-md-4">
        <div class="fw-bold mb-3">
            <i class="bi bi-lightning-charge-fill text-warning me-2"></i>
            Probá una consulta rápida
        </div>

        <div class="d-flex flex-wrap gap-2">
            <button
                type="button"
                class="btn btn-outline-success quick-query"
                data-q="¿Qué productos tienen stock crítico?"
            >
                Stock crítico
            </button>

            <button
                type="button"
                class="btn btn-outline-success quick-query"
                data-q="¿Cuántas variedades de plantas y flores tenemos y cuáles son?"
            >
                Variedades de plantas
            </button>

            <button
                type="button"
                class="btn btn-outline-success quick-query"
                data-q="¿Qué sustratos tenemos y cuáles son sus precios?"
            >
                Precios de sustratos
            </button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('formChat');
    const input = document.getElementById('inputPregunta');
    const button = document.getElementById('btnEnviar');

    if (!form || !input || !button) {
        console.error('No se encontraron los elementos del chat.');
        return;
    }

    function agregarMensaje(texto, esUsuario = false, esEspera = false) {
        // Usa el mismo contenedor y las mismas clases que el mensaje de bienvenida.
        const contenedor = document.getElementById('chatContainer');

        if (!contenedor) {
            console.error('No se encontró el contenedor de mensajes.');
            return { remove() {} };
        }

        // Estructura: <div class="chat-message [user]"> [avatar] <div class="chat-bubble">
        const mensaje = document.createElement('div');
        mensaje.className = esUsuario ? 'chat-message user' : 'chat-message';

        if (!esUsuario) {
            const avatar = document.createElement('div');
            avatar.className = 'chat-avatar';
            avatar.innerHTML = '<i class="bi bi-robot"></i>';
            mensaje.appendChild(avatar);
        }

        const burbuja = document.createElement('div');
        burbuja.className = 'chat-bubble';
        // textContent (no innerHTML) para que el texto nunca se interprete como HTML.
        burbuja.textContent = texto;

        if (esEspera) {
            burbuja.classList.add('text-muted', 'fst-italic');
        }

        mensaje.appendChild(burbuja);
        contenedor.appendChild(mensaje);
        contenedor.scrollTop = contenedor.scrollHeight;

        return {
            remove() {
                mensaje.remove();
            }
        };
    }

    async function consultar(pregunta) {
        if (!pregunta) {
            return;
        }

        input.disabled = true;
        button.disabled = true;

        agregarMensaje(pregunta, true);

        const espera = agregarMensaje(
            'Estoy revisando el inventario y preparando una respuesta…',
            false,
            true
        );

        try {
            const response = await fetch(
                'index.php?action=consultar_ia',
                {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        prompt: pregunta
                    })
                }
            );

            const textoRespuesta = await response.text();

            // Limpiar delimitadores Markdown si llegan del servidor.
            const respuestaLimpia = textoRespuesta
                .trim()
                .replace(/^```(?:php|json)?[ \t]*\r?\n?/i, '')
                .replace(/\r?\n?```[ \t]*$/, '')
                .trim();

            let data;

            try {
                data = JSON.parse(respuestaLimpia);
            } catch (error) {
                console.error(
                    'Respuesta original del servidor:',
                    textoRespuesta
                );

                console.error(
                    'Respuesta después de limpiar:',
                    respuestaLimpia
                );

                throw new Error(
                    'El servidor devolvió una respuesta que no es JSON válido.'
                );
            }

            espera.remove();

            if (!response.ok) {
                agregarMensaje(
                    data.respuesta ||
                    `El servidor respondió con el error HTTP ${response.status}.`
                );

                return;
            }

            if (
                typeof data.respuesta === 'string' &&
                data.respuesta.trim() !== ''
            ) {
                agregarMensaje(data.respuesta);
            } else {
                agregarMensaje(
                    'El servidor respondió, pero no envió un mensaje válido.'
                );
            }

        } catch (error) {
            espera.remove();

            console.error(
                'Error al consultar al asistente:',
                error
            );

            agregarMensaje(
                error.message ||
                'No pude comunicarme con el servidor. Revisá la conexión e intentá nuevamente.'
            );

        } finally {
            input.disabled = false;
            button.disabled = false;
            input.focus();
        }
    }

    form.addEventListener('submit', event => {
        event.preventDefault();
        consultar(input.value.trim());
        input.value = '';
    });

    document.querySelectorAll('.quick-query').forEach(btn => {
        btn.addEventListener('click', () => {
            consultar(btn.dataset.q || '');
        });
    });
});
</script>