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

// ---------------------------------------------------------------
// Ayudas de la nueva interfaz
// ---------------------------------------------------------------

// Silueta de usuario (avatar predeterminado)
function avatar_silueta() {
    return '<svg viewBox="0 0 100 100" role="img" aria-label="Sin foto de perfil"><circle cx="50" cy="34" r="19" fill="#e2b9a7"/><path d="M12 100c0-24 17-40 38-40s38 16 38 40z" fill="#e2b9a7"/></svg>';
}

// Contenido interno de un avatar: <img> si tiene foto, o la silueta
function avatar_interior($avatar, $nombre = '') {
    $url = avatar_url($avatar);
    if ($url) return '<img src="' . e($url) . '" alt="Foto de ' . e($nombre) . '">';
    return avatar_silueta();
}

// Estrella suelta (SVG) para los datos de las tarjetas
function icono_estrella() {
    return '<svg class="estrella" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M12 2.5l2.9 6.1 6.6.8-4.9 4.6 1.3 6.6L12 17.3 6.1 20.6l1.3-6.6L2.5 9.4l6.6-.8z"/></svg>';
}
function icono_reloj() {
    return '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9" fill="none" stroke="currentColor" stroke-width="2"/><path d="M12 7v5l3 2" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>';
}

// Divide las instrucciones en pasos (una línea = un paso).
// Si la línea tiene el formato "Título: texto" o "Título. Texto" corto, usa el título; si no, "Paso N".
function pasos_receta($texto) {
    $lineas = array_values(array_filter(array_map('trim', preg_split('/\R/', (string)$texto)), 'strlen'));
    // Si hay una sola línea larga, se separa por oraciones
    if (count($lineas) === 1 && mb_strlen($lineas[0]) > 160) {
        $partes = preg_split('/(?<=[.!?])\s+(?=[A-ZÁÉÍÓÚÑ¡¿])/u', $lineas[0]);
        if (count($partes) > 1) $lineas = array_map('trim', $partes);
    }
    $pasos = [];
    foreach ($lineas as $i => $l) {
        $l = preg_replace('/^\s*(\d+[\.\)\-:]|[-•*])\s*/u', '', $l); // quita "1." "2)" "-" del comienzo
        $titulo = 'Paso ' . ($i + 1);
        $cuerpo = $l;
        if (preg_match('/^([^:.]{3,40}):\s+(.+)$/us', $l, $m)) { $titulo = trim($m[1]); $cuerpo = $m[2]; }
        $pasos[] = ['titulo' => $titulo, 'texto' => $cuerpo];
    }
    return $pasos;
}

// Emoji aproximado para cada ingrediente (si no se reconoce, un cubierto)
function emoji_ingrediente($nombre) {
    $n = mb_strtolower($nombre);
    $mapa = [
        'fideo' => '🍝', 'spaghetti' => '🍝', 'espagueti' => '🍝', 'pasta' => '🍝', 'tallarin' => '🍝', 'ravio' => '🥟', 'ñoqui' => '🥔',
        'carne' => '🥩', 'bife' => '🥩', 'lomo' => '🥩', 'pollo' => '🍗', 'muslo' => '🍗', 'cerdo' => '🥓', 'panceta' => '🥓', 'jamón' => '🍖', 'jamon' => '🍖',
        'pescado' => '🐟', 'salm' => '🐟', 'atún' => '🐟', 'atun' => '🐟', 'camar' => '🦐', 'langost' => '🦞',
        'cebolla' => '🧅', 'ajo' => '🧄', 'tomate' => '🍅', 'papa' => '🥔', 'patata' => '🥔', 'zanahoria' => '🥕', 'morr' => '🫑', 'pimiento' => '🫑',
        'lechuga' => '🥬', 'espinaca' => '🥬', 'acelga' => '🥬', 'brócoli' => '🥦', 'brocoli' => '🥦', 'choclo' => '🌽', 'maíz' => '🌽', 'maiz' => '🌽',
        'pepino' => '🥒', 'berenjena' => '🍆', 'hongo' => '🍄', 'champi' => '🍄', 'palta' => '🥑', 'aguacate' => '🥑', 'arveja' => '🫛', 'poroto' => '🫘', 'lenteja' => '🫘',
        'huevo' => '🥚', 'leche' => '🥛', 'crema' => '🥛', 'manteca' => '🧈', 'mantequilla' => '🧈', 'queso' => '🧀', 'muzza' => '🧀', 'parmesano' => '🧀',
        'harina' => '🌾', 'arroz' => '🍚', 'pan' => '🍞', 'rallado' => '🍞', 'azúcar' => '🍬', 'azucar' => '🍬', 'miel' => '🍯', 'chocolate' => '🍫', 'cacao' => '🍫',
        'limón' => '🍋', 'limon' => '🍋', 'naranja' => '🍊', 'manzana' => '🍎', 'banana' => '🍌', 'frutill' => '🍓', 'frutilla' => '🍓', 'durazno' => '🍑', 'uva' => '🍇',
        'aceite' => '🫒', 'oliva' => '🫒', 'sal' => '🧂', 'pimienta' => '🧂', 'orégano' => '🌿', 'oregano' => '🌿', 'perejil' => '🌿', 'albahaca' => '🌿', 'laurel' => '🌿', 'romero' => '🌿',
        'agua' => '💧', 'vino' => '🍷', 'caldo' => '🍲', 'puré' => '🥫', 'pure' => '🥫', 'salsa' => '🥫', 'vainilla' => '🌼', 'canela' => '🌰', 'nuez' => '🌰', 'nueces' => '🌰', 'almendra' => '🌰',
    ];
    foreach ($mapa as $clave => $emoji) {
        if (mb_strpos($n, $clave) !== false) return $emoji;
    }
    return '🍴';
}

// Pasa "500 g de fideos" a un nombre corto para mostrar bajo el ícono ("Fideos")
function nombre_corto_ingrediente($linea) {
    $l = trim($linea);
    $l = preg_replace('/^[\d\s,\.\/½¼¾]+/u', '', $l);                                  // cantidad
    $l = preg_replace('/^(kg|kilos?|g|gr|grs|gramos?|ml|cc|l|lt|litros?|tazas?|cucharadas?|cucharaditas?|cdas?|cdtas?|unidad(es)?|dientes?|pizca|latas?|paquetes?)\b\.?\s*/iu', '', $l);
    $l = preg_replace('/^(de|del)\s+/iu', '', $l);
    $l = trim($l, " \t-–•*");
    $l = mb_strimwidth($l, 0, 34, '…');
    return $l !== '' ? mb_strtoupper(mb_substr($l, 0, 1)) . mb_substr($l, 1) : $linea;
}
