<?php
// contrasenaController.php - recuperar (olvidé mi contraseña, con código por correo) y cambiar contraseña
session_start();
require_once "includes/config.php";
require_once "includes/helpers.php";
require_once "includes/recuperacion.php";

$accion = $_GET['accion'] ?? '';

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: login.php");
    exit;
}

try {
    asegurar_tabla_recuperacion($pdo);
} catch (PDOException $e) {
    header("Location: login.php?error=error_servidor");
    exit;
}

// ==========================================
// 1. PEDIR EL CÓDIGO DE RECUPERACIÓN
// ==========================================
if ($accion === 'solicitar') {
    $email = trim($_POST['email'] ?? '');

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header("Location: recuperar.php?error=email_invalido");
        exit;
    }

    // Aviso de espera: se controla por sesión y por correo, así responde igual exista o no la cuenta
    $clave = strtolower($email);
    $faltan = RECUP_ESPERA_SEG - (time() - (int)($_SESSION['recup_pedidos'][$clave] ?? 0));
    if ($faltan > 0) {
        $_SESSION['recup_email'] = $email;
        header("Location: restablecer.php?aviso=espera&seg=" . $faltan);
        exit;
    }
    $_SESSION['recup_pedidos'][$clave] = time();

    try {
        $stmt = $pdo->prepare("SELECT id_usuario, nombre FROM USUARIO WHERE email = :email LIMIT 1");
        $stmt->execute([':email' => $email]);
        $usuario = $stmt->fetch();

        if ($usuario) {
            // Anti-spam: si ya pidió uno hace menos de RECUP_ESPERA_SEG segundos, no se genera otro
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM CODIGO_RECUPERACION WHERE id_usuario = :id AND creado > :limite");
            $stmt->execute([':id' => $usuario['id_usuario'], ':limite' => date('Y-m-d H:i:s', time() - RECUP_ESPERA_SEG)]);

            if ((int)$stmt->fetchColumn() === 0) {
                // Se invalidan los códigos anteriores: solo vale el último
                $pdo->prepare("UPDATE CODIGO_RECUPERACION SET usado = 1 WHERE id_usuario = :id AND usado = 0")
                    ->execute([':id' => $usuario['id_usuario']]);

                $codigo = generar_codigo();
                $pdo->prepare("INSERT INTO CODIGO_RECUPERACION (id_usuario, codigo_hash, expira, creado)
                               VALUES (:id, :h, :expira, :creado)")
                    ->execute([
                        ':id'     => $usuario['id_usuario'],
                        ':h'      => password_hash($codigo, PASSWORD_BCRYPT),   // en la BD solo se guarda el hash
                        ':expira' => date('Y-m-d H:i:s', time() + CODIGO_VIGENCIA_SEG),
                        ':creado' => date('Y-m-d H:i:s'),
                    ]);

                enviar_correo_codigo($email, $usuario['nombre'], $codigo);

                // Solo en localhost: se muestra el código en pantalla para poder probar sin servidor de correo
                if (es_entorno_local()) $_SESSION['dev_codigo_recuperacion'] = $codigo;
            }
        } else {
            unset($_SESSION['dev_codigo_recuperacion']);
        }
        // Siempre se pasa a la pantalla del código, exista o no el correo (así no se puede averiguar quién está registrado)
        $_SESSION['recup_email'] = $email;
        header("Location: restablecer.php");
        exit;
    } catch (PDOException $e) {
        header("Location: recuperar.php?error=error_servidor");
        exit;
    }
}

// ==========================================
// 2. RESTABLECER CON EL CÓDIGO DEL CORREO
// ==========================================
if ($accion === 'restablecer') {
    $email     = $_SESSION['recup_email'] ?? '';
    $codigo    = $_POST['codigo'] ?? '';
    $nueva     = $_POST['contrasena'] ?? '';
    $confirmar = $_POST['confirmar_contrasena'] ?? '';

    if ($email === '') {
        header("Location: recuperar.php");
        exit;
    }

    // Primero se validan las contraseñas: un error de tipeo ahí no gasta intentos del código
    if ($codigo === '' || $nueva === '') { header("Location: restablecer.php?error=campos_incompletos"); exit; }
    if ($nueva !== $confirmar) { header("Location: restablecer.php?error=contrasenas_no_coinciden"); exit; }
    if (strlen($nueva) < CONTRASENA_MIN) { header("Location: restablecer.php?error=contrasena_corta"); exit; }

    try {
        $fila = verificar_codigo($pdo, $email, $codigo);
        if (!$fila) {
            // Mismo mensaje si el código es incorrecto, venció, se agotaron los intentos o el correo no existe
            header("Location: restablecer.php?error=codigo_invalido");
            exit;
        }

        // Datos del usuario (ya se comprobó el código, así que acá no se filtra nada)
        $stmt = $pdo->prepare("SELECT nombre, email, contrasena FROM USUARIO WHERE id_usuario = :id LIMIT 1");
        $stmt->execute([':id' => $fila['id_usuario']]);
        $u = $stmt->fetch();

        if ($u && password_verify($nueva, $u['contrasena'])) {
            header("Location: restablecer.php?error=contrasena_igual");
            exit;
        }

        $pdo->beginTransaction();
        $pdo->prepare("UPDATE USUARIO SET contrasena = :c WHERE id_usuario = :id")
            ->execute([':c' => password_hash($nueva, PASSWORD_BCRYPT), ':id' => $fila['id_usuario']]);
        // El código queda usado (y cualquier otro pendiente de ese usuario también)
        $pdo->prepare("UPDATE CODIGO_RECUPERACION SET usado = 1 WHERE id_usuario = :id")
            ->execute([':id' => $fila['id_usuario']]);
        $pdo->commit();

        unset($_SESSION['recup_email'], $_SESSION['dev_codigo_recuperacion']);
        if ($u) enviar_aviso_cambio($u['email'], $u['nombre']);
        header("Location: login.php?contrasena=restablecida");
        exit;
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        header("Location: restablecer.php?error=error_servidor");
        exit;
    }
}

// ==========================================
// 3. CAMBIAR CONTRASEÑA (usuario con sesión iniciada)
// ==========================================
if ($accion === 'cambiar') {
    requiere_login();
    $idUsuario = (int)$_SESSION['id_usuario'];
    $actual    = $_POST['contrasena_actual'] ?? '';
    $nueva     = $_POST['contrasena_nueva'] ?? '';
    $confirmar = $_POST['confirmar_contrasena'] ?? '';

    if ($actual === '' || $nueva === '' || $confirmar === '') {
        header("Location: cambiar_contrasena.php?error=campos_incompletos"); exit;
    }
    if ($nueva !== $confirmar) {
        header("Location: cambiar_contrasena.php?error=contrasenas_no_coinciden"); exit;
    }
    if (strlen($nueva) < CONTRASENA_MIN) {
        header("Location: cambiar_contrasena.php?error=contrasena_corta"); exit;
    }

    try {
        $stmt = $pdo->prepare("SELECT contrasena, nombre, email FROM USUARIO WHERE id_usuario = :id LIMIT 1");
        $stmt->execute([':id' => $idUsuario]);
        $datos = $stmt->fetch();
        $hashActual = $datos['contrasena'] ?? '';

        if (!$hashActual || !password_verify($actual, $hashActual)) {
            header("Location: cambiar_contrasena.php?error=contrasena_actual_incorrecta"); exit;
        }
        if ($actual === $nueva) {
            header("Location: cambiar_contrasena.php?error=contrasena_igual"); exit;
        }

        $pdo->beginTransaction();
        $pdo->prepare("UPDATE USUARIO SET contrasena = :c WHERE id_usuario = :id")
            ->execute([':c' => password_hash($nueva, PASSWORD_BCRYPT), ':id' => $idUsuario]);
        // Si había códigos de recuperación pendientes, ya no sirven
        $pdo->prepare("UPDATE CODIGO_RECUPERACION SET usado = 1 WHERE id_usuario = :id")
            ->execute([':id' => $idUsuario]);
        $pdo->commit();

        session_regenerate_id(true);
        enviar_aviso_cambio($datos['email'], $datos['nombre']);
        header("Location: cambiar_contrasena.php?msg=cambiada");
        exit;
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        header("Location: cambiar_contrasena.php?error=error_servidor");
        exit;
    }
}

header("Location: login.php");
exit;
