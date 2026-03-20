<?php
/**
 * contact.php — Procesador del formulario de contacto
 * Dra. Lupita Arcuri | Psicóloga Clínica
 *
 * Seguridad implementada:
 * - Validación server-side de todos los campos requeridos
 * - Sanitización de inputs con filter_var y htmlspecialchars
 * - Protección CSRF con token en sesión
 * - Rate limiting básico mediante sesión
 * - Solo acepta POST; rechaza GET directos
 */

declare(strict_types=1);

// ─── CONFIGURACIÓN ────────────────────────────────────────────────
// IMPORTANTE: Antes de producción, reemplazá estos valores con los reales.
const DESTINATARIO = 'lupita.arcuri@psicologia.com';
const NOMBRE_SITIO = 'Dra. Lupita Arcuri — Psicóloga Clínica';
const URL_FORMULARIO = 'index.php#contacto'; // URL de vuelta tras el envío

// ─── INICIO DE SESIÓN (necesario para CSRF y rate limiting) ───────
session_start();

// ─── SOLO SE PERMITE MÉTODO POST ──────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

// ─── VALIDACIÓN DEL TOKEN CSRF ─────────────────────────────────────
$tokenEnviado   = $_POST['csrf_token'] ?? '';
$tokenSesion    = $_SESSION['csrf_token'] ?? '';

if (empty($tokenEnviado) || !hash_equals($tokenSesion, $tokenEnviado)) {
    // Token inválido o ausente — posible ataque CSRF
    $_SESSION['form_error'] = 'Token de seguridad inválido. Por favor, recargá la página e intentá de nuevo.';
    header('Location: ' . URL_FORMULARIO);
    exit;
}

// Regenerar el token para el próximo envío (no reutilizar)
unset($_SESSION['csrf_token']);

// ─── RATE LIMITING básico — máximo 3 envíos por sesión en 10 min ─
$ahora = time();
if (!isset($_SESSION['form_envios'])) {
    $_SESSION['form_envios'] = [];
}

// Limpiar envíos de más de 10 minutos atrás
$_SESSION['form_envios'] = array_filter(
    $_SESSION['form_envios'],
    fn(int $ts) => ($ahora - $ts) < 600
);

if (count($_SESSION['form_envios']) >= 3) {
    $_SESSION['form_error'] = 'Enviaste demasiados mensajes en poco tiempo. Por favor, esperá unos minutos antes de intentar de nuevo.';
    header('Location: ' . URL_FORMULARIO);
    exit;
}

// ─── SANITIZACIÓN Y VALIDACIÓN DE CAMPOS ─────────────────────────
$errores = [];

/**
 * Limpia un string: elimina tags HTML, espacios extra y normaliza.
 */
function limpiarString(string $valor): string {
    return trim(strip_tags($valor));
}

// Nombre (requerido, 2–100 chars)
$nombre = limpiarString($_POST['nombre'] ?? '');
if (empty($nombre)) {
    $errores['nombre'] = 'El nombre es obligatorio.';
} elseif (mb_strlen($nombre) < 2 || mb_strlen($nombre) > 100) {
    $errores['nombre'] = 'El nombre debe tener entre 2 y 100 caracteres.';
}

// Email (requerido, formato válido)
$email = limpiarString($_POST['email'] ?? '');
if (empty($email)) {
    $errores['email'] = 'El email es obligatorio.';
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errores['email'] = 'El formato del email no es válido.';
} elseif (mb_strlen($email) > 254) {
    $errores['email'] = 'El email es demasiado largo.';
}

// Teléfono (opcional, formato básico si se ingresa)
$telefono = limpiarString($_POST['telefono'] ?? '');
if (!empty($telefono)) {
    // Permitir dígitos, espacios, guiones, paréntesis y "+"
    if (!preg_match('/^[\d\s\+\-\(\)\.]{6,25}$/', $telefono)) {
        $errores['telefono'] = 'El teléfono ingresado no tiene un formato válido.';
    }
}

// Modalidad (requerida, valores permitidos)
$modalidadesPermitidas = ['Presencial', 'Online', 'Indistinto'];
$modalidad = limpiarString($_POST['modalidad'] ?? '');
if (empty($modalidad) || !in_array($modalidad, $modalidadesPermitidas, true)) {
    $errores['modalidad'] = 'Por favor, seleccioná una modalidad.';
}

// Mensaje (opcional, máx 2000 chars si se ingresa)
$mensaje = limpiarString($_POST['mensaje'] ?? '');
if (!empty($mensaje) && mb_strlen($mensaje) > 2000) {
    $errores['mensaje'] = 'El mensaje no puede superar los 2000 caracteres.';
}

// ─── SI HAY ERRORES, VOLVER AL FORMULARIO CON LOS DATOS ───────────
if (!empty($errores)) {
    $_SESSION['form_errores'] = $errores;
    $_SESSION['form_datos']   = compact('nombre', 'email', 'telefono', 'modalidad', 'mensaje');
    header('Location: ' . URL_FORMULARIO);
    exit;
}

// ─── CONSTRUCCIÓN DEL EMAIL ───────────────────────────────────────
$asunto = '=?UTF-8?B?' . base64_encode('Nuevo contacto desde el sitio web — ' . $nombre) . '?=';

// Cabeceras del email
$headers  = 'From: ' . NOMBRE_SITIO . ' <noreply@psicologia.com>' . "\r\n";
$headers .= 'Reply-To: ' . $nombre . ' <' . $email . '>' . "\r\n";
$headers .= 'Content-Type: text/plain; charset=UTF-8' . "\r\n";
$headers .= 'Content-Transfer-Encoding: 8bit' . "\r\n";
$headers .= 'X-Mailer: PHP/' . phpversion() . "\r\n";

// Cuerpo del email (texto plano — sin HTML para evitar filtros de spam)
$cuerpo  = "Nuevo mensaje de contacto recibido desde el sitio web.\n";
$cuerpo .= str_repeat('─', 50) . "\n\n";
$cuerpo .= "Nombre:    " . $nombre . "\n";
$cuerpo .= "Email:     " . $email . "\n";
$cuerpo .= "Teléfono:  " . ($telefono ?: 'No informado') . "\n";
$cuerpo .= "Modalidad: " . $modalidad . "\n\n";
$cuerpo .= "Mensaje:\n" . ($mensaje ?: 'Sin mensaje adicional.') . "\n\n";
$cuerpo .= str_repeat('─', 50) . "\n";
$cuerpo .= "Enviado el: " . date('d/m/Y H:i:s') . "\n";
$cuerpo .= "IP origen: " . filter_var($_SERVER['REMOTE_ADDR'] ?? '', FILTER_VALIDATE_IP) . "\n";

// ─── ENVÍO DEL EMAIL ──────────────────────────────────────────────
$enviado = mail(DESTINATARIO, $asunto, $cuerpo, $headers);

if ($enviado) {
    // Registrar el envío para rate limiting
    $_SESSION['form_envios'][] = $ahora;

    // Limpiar datos previos de sesión
    unset($_SESSION['form_errores'], $_SESSION['form_datos']);

    // Redirigir con parámetro de éxito
    header('Location: index.php?sent=1#contacto');
} else {
    // El servidor no pudo enviar el email
    // En producción, loggear este error con error_log()
    $_SESSION['form_error'] = 'Hubo un problema al enviar tu mensaje. Por favor, intentá de nuevo o escribinos directamente a lupita.arcuri@psicologia.com.';
    $_SESSION['form_datos'] = compact('nombre', 'email', 'telefono', 'modalidad', 'mensaje');
    header('Location: ' . URL_FORMULARIO);
}

exit;
