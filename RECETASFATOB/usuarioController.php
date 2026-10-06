<?php
session_start();
require_once "includes/config.php";

$accion = $_GET['accion'] ?? '';

// Editar y eliminar usuarios: solo administrador
if (in_array($accion, ['editar', 'eliminar'], true) && (int)($_SESSION['id_rol'] ?? 0) !== 1) {
    header("Location: login.php?error=acceso_denegado");
    exit;
}

// ==========================================
// 1. REGISTRO / CREAR USUARIO (CREATE)
// ==========================================
if ($_SERVER["REQUEST_METHOD"] === "POST" && $accion === 'registro') {
    $nombre = trim($_POST['nombre'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $contrasena = $_POST['contrasena'] ?? '';
    $restricciones = $_POST['restricciones'] ?? [];

    if (empty($nombre) || empty($email) || empty($contrasena)) {
        header("Location: registro.php?error=campos_incompletos");
        exit;
    }

    try {
        $stmtCheck = $pdo->prepare("SELECT id_usuario FROM USUARIO WHERE email = :email LIMIT 1");
        $stmtCheck->execute([':email' => $email]);

        if ($stmtCheck->fetch()) {
            header("Location: registro.php?error=email_existente");
            exit;
        }

        $passHash = password_hash($contrasena, PASSWORD_BCRYPT);

        $sql = "INSERT INTO USUARIO (id_rol, nombre, email, contrasena, avatar, fecha_registro)
                VALUES (2, :nombre, :email, :contrasena, 'default_avatar.png', NOW())";
       
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':nombre'     => $nombre,
            ':email'      => $email,
            ':contrasena' => $passHash
        ]);

        $idUsuarioNuevo = $pdo->lastInsertId();

        if (!empty($restricciones) && is_array($restricciones)) {
            $sqlRest = "INSERT INTO USUARIO_RESTRICCION (id_usuario, id_restriccion) VALUES (:id_usuario, :id_restriccion)";
            $stmtRest = $pdo->prepare($sqlRest);

            foreach ($restricciones as $idRestriccion) {
                $stmtRest->execute([
                    ':id_usuario'     => $idUsuarioNuevo,
                    ':id_restriccion' => (int)$idRestriccion
                ]);
            }
        }

        header("Location: login.php?registro=exito");
        exit;

    } catch (PDOException $e) {
        header("Location: registro.php?error=error_servidor");
        exit;
    }
}

// ==========================================
// 2. INICIAR SESIÓN (LOGIN)
// ==========================================
if ($_SERVER["REQUEST_METHOD"] === "POST" && $accion === 'login') {
    $email = trim($_POST['email'] ?? '');
    $contrasena = $_POST['contrasena'] ?? '';

    if (empty($email) || empty($contrasena)) {
        header("Location: login.php?error=campos_incompletos");
        exit;
    }

    try {
        $stmt = $pdo->prepare("SELECT * FROM USUARIO WHERE email = :email LIMIT 1");
        $stmt->execute([':email' => $email]);
        $usuario = $stmt->fetch();

        if ($usuario && password_verify($contrasena, $usuario['contrasena'])) {
            $_SESSION['id_usuario'] = $usuario['id_usuario'];
            $_SESSION['nombre']     = $usuario['nombre'];
            $_SESSION['id_rol']     = $usuario['id_rol'];
            $_SESSION['email']      = $usuario['email'];

            header("Location: perfil.php");
            exit;
        } else {
            header("Location: login.php?error=credenciales_invalidas");
            exit;
        }
    } catch (PDOException $e) {
        header("Location:  login.php?error=error_servidor");
        exit;
    }
}

// ==========================================
// EDITAR MI PERFIL (el propio usuario)
// ==========================================
if ($_SERVER["REQUEST_METHOD"] === "POST" && $accion === 'editar_perfil') {
    if (!isset($_SESSION['id_usuario'])) {
        header("Location: login.php?error=acceso_denegado");
        exit;
    }
    $idUsuario     = (int)$_SESSION['id_usuario'];
    $nombre        = trim($_POST['nombre'] ?? '');
    $email         = trim($_POST['email'] ?? '');
    $contrasena    = $_POST['contrasena_nueva'] ?? '';
    $restricciones = $_POST['restricciones'] ?? [];

    if ($nombre === '' || $email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header("Location: perfil.php?editar=1&error=campos_incompletos");
        exit;
    }
    if ($contrasena !== '' && strlen($contrasena) < 8) {
        header("Location: perfil.php?editar=1&error=contrasena_corta");
        exit;
    }

    try {
        // El email no puede pertenecer a otro usuario
        $stmt = $pdo->prepare("SELECT id_usuario FROM USUARIO WHERE email = :email AND id_usuario <> :id LIMIT 1");
        $stmt->execute([':email' => $email, ':id' => $idUsuario]);
        if ($stmt->fetch()) {
            header("Location: perfil.php?editar=1&error=email_existente");
            exit;
        }

        $pdo->beginTransaction();

        if ($contrasena !== '') {
            $stmt = $pdo->prepare("UPDATE USUARIO SET nombre = :n, email = :e, contrasena = :c WHERE id_usuario = :id");
            $stmt->execute([':n' => $nombre, ':e' => $email, ':c' => password_hash($contrasena, PASSWORD_BCRYPT), ':id' => $idUsuario]);
        } else {
            $stmt = $pdo->prepare("UPDATE USUARIO SET nombre = :n, email = :e WHERE id_usuario = :id");
            $stmt->execute([':n' => $nombre, ':e' => $email, ':id' => $idUsuario]);
        }

        // Reemplazar restricciones dietarias
        $pdo->prepare("DELETE FROM USUARIO_RESTRICCION WHERE id_usuario = :id")->execute([':id' => $idUsuario]);
        if (is_array($restricciones)) {
            $ins = $pdo->prepare("INSERT INTO USUARIO_RESTRICCION (id_usuario, id_restriccion) VALUES (:u, :r)");
            foreach (array_unique($restricciones) as $idRestriccion) {
                $ins->execute([':u' => $idUsuario, ':r' => (int)$idRestriccion]);
            }
        }

        $pdo->commit();
        $_SESSION['nombre'] = $nombre;
        header("Location: perfil.php?msg=actualizado");
        exit;
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        header("Location: perfil.php?editar=1&error=error_servidor");
        exit;
    }
}

// ==========================================
// 3. EDITAR USUARIO (UPDATE)
// ==========================================
if ($_SERVER["REQUEST_METHOD"] === "POST" && $accion === 'editar') {
    $id_usuario = (int)($_POST['id_usuario'] ?? 0);
    $nombre     = trim($_POST['nombre'] ?? '');
    $email      = trim($_POST['email'] ?? '');
    $id_rol     = (int)($_POST['id_rol'] ?? 2);

    if ($id_usuario > 0 && !empty($nombre) && !empty($email)) {
        try {
            $sql = "UPDATE USUARIO SET nombre = :nombre, email = :email, id_rol = :id_rol WHERE id_usuario = :id_usuario";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':nombre'     => $nombre,
                ':email'      => $email,
                ':id_rol'     => $id_rol,
                ':id_usuario' => $id_usuario
            ]);

            // CORREGIDO: Redirige a usuario.php en la raíz
            header("Location: usuario.php?msg=editado");
            exit;
        } catch (PDOException $e) {
            header("Location: usuario.php?error=error_servidor");
            exit;
        }
    }
}

// ==========================================
// 4. ELIMINAR USUARIO (DELETE)
// ==========================================
if ($accion === 'eliminar') {
    $id_usuario = (int)($_GET['id'] ?? 0);

    if ($id_usuario > 0) {
        try {
            $stmt = $pdo->prepare("DELETE FROM USUARIO WHERE id_usuario = :id_usuario");
            $stmt->execute([':id_usuario' => $id_usuario]);

            // CORREGIDO: Redirige a usuario.php en la raíz
            header("Location: usuario.php?msg=eliminado");
            exit;
        } catch (PDOException $e) {
            header("Location: usuario.php?error=error_servidor");
            exit;
        }
    }
}

// ==========================================
// 5. CERRAR SESIÓN (LOGOUT)
// ==========================================
if ($accion === 'logout') {
    session_destroy();
    header("Location: login.php?logout=exito");
    exit;
}
?>