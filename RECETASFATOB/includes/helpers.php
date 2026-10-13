<?php
// includes/helpers.php - funciones comunes (requiere session_start() previo)

// Máximo de fotos por receta
const MAX_FOTOS_RECETA = 5;

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
// $carpeta: subcarpeta de img/ donde se guarda ('recetas' por defecto, 'avatares' para fotos de perfil)
function subir_imagen($file, $carpeta = 'recetas', $prefijo = 'receta_') {
    if (!isset($file) || $file['error'] === UPLOAD_ERR_NO_FILE) return null;
    if ($file['error'] !== UPLOAD_ERR_OK || $file['size'] > 2 * 1024 * 1024) return false;
    $info = @getimagesize($file['tmp_name']);
    $permitidos = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
    if (!$info || !isset($permitidos[$info['mime']])) return false;
    $nombre = uniqid($prefijo, true) . '.' . $permitidos[$info['mime']];
    $destino = __DIR__ . '/../img/' . $carpeta . '/' . $nombre;
    return move_uploaded_file($file['tmp_name'], $destino) ? $nombre : false;
}

// Cuenta cuántos archivos se eligieron en un <input type="file" name="imagenes[]" multiple>
function contar_subidas($files) {
    if (!isset($files['error']) || !is_array($files['error'])) return 0;
    $n = 0;
    foreach ($files['error'] as $err) if ($err !== UPLOAD_ERR_NO_FILE) $n++;
    return $n;
}

// Sube varias imágenes a la vez.
// Devuelve: array con los nombres guardados (vacío si no se eligió ninguna) | false si alguna falla
// (en ese caso borra las que ya se habían guardado, para no dejar archivos sueltos)
function subir_imagenes($files, $max = MAX_FOTOS_RECETA) {
    if (!isset($files['error']) || !is_array($files['error'])) return [];
    $guardadas = [];
    foreach ($files['error'] as $i => $err) {
        if ($err === UPLOAD_ERR_NO_FILE) continue;
        $nombre = false;
        if (count($guardadas) < $max) {
            $nombre = subir_imagen([
                'error'    => $err,
                'size'     => $files['size'][$i],
                'tmp_name' => $files['tmp_name'][$i],
                'name'     => $files['name'][$i],
            ]);
        }
        if (!$nombre) {
            foreach ($guardadas as $g) borrar_imagen($g);
            return false;
        }
        $guardadas[] = $nombre;
    }
    return $guardadas;
}

// Fotos de una receta, en orden: [['id' => ..., 'archivo' => ...], ...]
// Las recetas viejas (con una sola foto en RECETA.imagen) se siguen mostrando.
// $migrar = true (al editar) las pasa a la tabla RECETA_IMAGEN para poder quitarlas o sumarles más fotos.
function imagenes_receta($pdo, $receta, $migrar = false) {
    try {
        $stmt = $pdo->prepare("SELECT id_imagen AS id, archivo FROM RECETA_IMAGEN WHERE id_receta = :r ORDER BY orden, id_imagen");
        $stmt->execute([':r' => $receta['id_receta']]);
        $fotos = $stmt->fetchAll();
        if (!$fotos && !empty($receta['imagen']) && $migrar) {
            $pdo->prepare("INSERT INTO RECETA_IMAGEN (id_receta, archivo, orden) VALUES (:r, :a, 0)")
                ->execute([':r' => $receta['id_receta'], ':a' => $receta['imagen']]);
            $stmt->execute([':r' => $receta['id_receta']]);
            $fotos = $stmt->fetchAll();
        }
    } catch (PDOException $e) {
        $fotos = [];
    }
    if (!$fotos && !empty($receta['imagen'])) {
        $fotos = [['id' => 0, 'archivo' => $receta['imagen']]];
    }
    return $fotos;
}

function borrar_imagen($nombre, $carpeta = 'recetas') {
    if (!$nombre) return;
    $ruta = __DIR__ . '/../img/' . $carpeta . '/' . basename($nombre);
    if (is_file($ruta)) @unlink($ruta);
}

// Devuelve la ruta del avatar del usuario, o null si no tiene foto (se muestra el predeterminado)
function avatar_url($avatar) {
    if (!$avatar || $avatar === 'default_avatar.png') return null;
    $archivo = basename($avatar);
    return is_file(__DIR__ . '/../img/avatares/' . $archivo) ? 'img/avatares/' . rawurlencode($archivo) : null;
}

// ¿La receta está en las favoritas del usuario que inició sesión?
function es_favorita($pdo, $idReceta) {
    if (!logueado()) return false;
    $stmt = $pdo->prepare("SELECT 1 FROM FAVORITO WHERE id_usuario = :u AND id_receta = :r");
    $stmt->execute([':u' => $_SESSION['id_usuario'], ':r' => (int)$idReceta]);
    return (bool)$stmt->fetchColumn();
}

// Ids de todas las recetas favoritas del usuario logueado (una sola consulta para los listados)
function ids_favoritas($pdo) {
    if (!logueado()) return [];
    $stmt = $pdo->prepare("SELECT id_receta FROM FAVORITO WHERE id_usuario = :u");
    $stmt->execute([':u' => $_SESSION['id_usuario']]);
    return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
}
