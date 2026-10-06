<?php
// includes/helpers.php - funciones comunes (requiere session_start() previo)

function e($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function logueado() { return isset($_SESSION['id_usuario']); }
function es_admin() { return logueado() && (int)($_SESSION['id_rol'] ?? 0) === 1; }

function requiere_login() {
    if (!logueado()) {
        header("Location: login.php?error=acceso_denegado");
        exit;
    }
}

function puede_editar($idAutor) {
    return logueado() && (es_admin() || (int)$_SESSION['id_usuario'] === (int)$idAutor);
}

function estrellas($n) {
    $n = (int)round($n);
    return str_repeat('★', $n) . str_repeat('☆', 5 - $n);
}

// Devuelve: nombre del archivo guardado | null (no se subió nada) | false (error)
function subir_imagen($file) {
    if (!isset($file) || $file['error'] === UPLOAD_ERR_NO_FILE) return null;
    if ($file['error'] !== UPLOAD_ERR_OK || $file['size'] > 2 * 1024 * 1024) return false;
    $info = @getimagesize($file['tmp_name']);
    $permitidos = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
    if (!$info || !isset($permitidos[$info['mime']])) return false;
    $nombre = uniqid('receta_', true) . '.' . $permitidos[$info['mime']];
    $destino = __DIR__ . '/../img/recetas/' . $nombre;
    return move_uploaded_file($file['tmp_name'], $destino) ? $nombre : false;
}

function borrar_imagen($nombre) {
    if (!$nombre) return;
    $ruta = __DIR__ . '/../img/recetas/' . basename($nombre);
    if (is_file($ruta)) @unlink($ruta);
}
