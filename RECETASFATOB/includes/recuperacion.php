<?php
// includes/recuperacion.php - funciones compartidas para recuperar/cambiar contraseña (con código de 6 dígitos)

const CODIGO_VIGENCIA_SEG = 600;  // el código dura 10 minutos
const CODIGO_MAX_INTENTOS = 5;    // después de 5 intentos fallidos hay que pedir otro código
const RECUP_ESPERA_SEG    = 60;   // mínimo entre un pedido y otro para el mismo usuario
const CONTRASENA_MIN      = 8;

// Crea la tabla de códigos si todavía no existe (así no hace falta correr el SQL a mano)
function asegurar_tabla_recuperacion(PDO $pdo) {
    $pdo->exec("CREATE TABLE IF NOT EXISTS CODIGO_RECUPERACION (
        id_codigo INT AUTO_INCREMENT PRIMARY KEY,
        id_usuario INT NOT NULL,
        codigo_hash VARCHAR(255) NOT NULL,
        expira DATETIME NOT NULL,
        intentos TINYINT NOT NULL DEFAULT 0,
        usado TINYINT(1) NOT NULL DEFAULT 0,
        creado DATETIME NOT NULL,
        FOREIGN KEY (id_usuario) REFERENCES USUARIO(id_usuario) ON DELETE CASCADE
    )");
}

// Código de 6 dígitos con aleatoriedad segura (random_int, no rand/mt_rand)
function generar_codigo() {
    return str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
}

// Comprueba el código que escribió el usuario. Devuelve la fila del código si es correcto, o false.
// Cada intento se cuenta ANTES de comparar, así que no se puede pasar del máximo ni con pedidos simultáneos.
function verificar_codigo(PDO $pdo, $email, $codigo) {
    $codigo = preg_replace('/\s+/', '', (string)$codigo);
    if (!preg_match('/^\d{6}$/', $codigo)) return false;

    $stmt = $pdo->prepare("SELECT id_usuario FROM USUARIO WHERE email = :e LIMIT 1");
    $stmt->execute([':e' => $email]);
    $idUsuario = $stmt->fetchColumn();
    if (!$idUsuario) return false;

    // Último código vigente de ese usuario
    $stmt = $pdo->prepare("SELECT id_codigo, id_usuario, codigo_hash FROM CODIGO_RECUPERACION
                           WHERE id_usuario = :id AND usado = 0 AND expira > :ahora
                           ORDER BY id_codigo DESC LIMIT 1");
    $stmt->execute([':id' => $idUsuario, ':ahora' => date('Y-m-d H:i:s')]);
    $fila = $stmt->fetch();
    if (!$fila) return false;

    // Se suma el intento; si ya llegó al máximo, no se actualiza nada y se rechaza
    $stmt = $pdo->prepare("UPDATE CODIGO_RECUPERACION SET intentos = intentos + 1
                           WHERE id_codigo = :c AND intentos < :max");
    $stmt->execute([':c' => $fila['id_codigo'], ':max' => CODIGO_MAX_INTENTOS]);
    if ($stmt->rowCount() === 0) return false;

    return password_verify($codigo, $fila['codigo_hash']) ? $fila : false;
}

function es_entorno_local() {
    return in_array($_SERVER['SERVER_NAME'] ?? '', ['localhost', '127.0.0.1', '::1'], true);
}

// Envía el correo con el código. En localhost (XAMPP no manda mails) además lo deja en logs/correos.log
function enviar_correo_codigo($email, $nombre, $codigo) {
    $asunto = 'Tu código para recuperar la contraseña - RECETAS FATOB';
    $cuerpo = "Hola $nombre,\r\n\r\n"
            . "Recibimos un pedido para restablecer tu contraseña.\r\n"
            . "Tu código es:  $codigo\r\n\r\n"
            . "Vale por 10 minutos. Escribilo en la página para elegir tu nueva contraseña.\r\n\r\n"
            . "Si no fuiste vos, ignorá este mensaje: tu contraseña no cambia.\r\n";
    $headers = "From: RECETAS FATOB <no-reply@" . ($_SERVER['SERVER_NAME'] ?? 'localhost') . ">\r\n"
             . "Content-Type: text/plain; charset=UTF-8\r\n";

    if (es_entorno_local()) {
        $dir = __DIR__ . '/../logs';
        if (!is_dir($dir)) @mkdir($dir, 0755, true);
        @file_put_contents($dir . '/correos.log',
            "[" . date('Y-m-d H:i:s') . "] Para: $email\nAsunto: $asunto\n$cuerpo\n----------\n", FILE_APPEND);
    }
    return @mail($email, mb_encode_mimeheader($asunto, 'UTF-8'), $cuerpo, $headers);
}

// Avisa por correo que la contraseña fue cambiada (por si no fue la persona dueña de la cuenta)
function enviar_aviso_cambio($email, $nombre) {
    $asunto = 'Tu contraseña fue cambiada - RECETAS FATOB';
    $cuerpo = "Hola $nombre,\r\n\r\n"
            . "Te avisamos que la contraseña de tu cuenta fue cambiada el " . date('d/m/Y \a \l\a\s H:i') . ".\r\n\r\n"
            . "Si fuiste vos, no tenés que hacer nada.\r\n"
            . "Si NO fuiste vos, pedí cuanto antes una contraseña nueva desde \"¿Olvidaste tu contraseña?\" "
            . "y avisanos.\r\n";
    $headers = "From: RECETAS FATOB <no-reply@" . ($_SERVER['SERVER_NAME'] ?? 'localhost') . ">\r\n"
             . "Content-Type: text/plain; charset=UTF-8\r\n";

    if (es_entorno_local()) {
        $dir = __DIR__ . '/../logs';
        if (!is_dir($dir)) @mkdir($dir, 0755, true);
        @file_put_contents($dir . '/correos.log',
            "[" . date('Y-m-d H:i:s') . "] Para: $email\nAsunto: $asunto\n$cuerpo\n----------\n", FILE_APPEND);
    }
    return @mail($email, mb_encode_mimeheader($asunto, 'UTF-8'), $cuerpo, $headers);
}
