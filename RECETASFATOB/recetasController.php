<?php
session_start();
require_once "includes/config.php";
require_once "includes/helpers.php";
requiere_login();

$accion = $_GET['accion'] ?? '';

function datos_receta() {
    return [
        'titulo'        => trim($_POST['titulo'] ?? ''),
        'descripcion'   => trim($_POST['descripcion'] ?? ''),
        'ingredientes'  => trim($_POST['ingredientes'] ?? ''),
        'instrucciones' => trim($_POST['instrucciones'] ?? ''),
        'tiempo'        => (int)($_POST['tiempo_preparacion'] ?? 0),
    ];
}

// ==========================================
// CREAR RECETA
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $accion === 'crear') {
    $d = datos_receta();
    if ($d['titulo'] === '' || $d['descripcion'] === '' || $d['ingredientes'] === '' || $d['instrucciones'] === '' || $d['tiempo'] < 1) {
        header("Location: receta_form.php?error=campos_incompletos");
        exit;
    }
    $imagen = subir_imagen($_FILES['imagen'] ?? null);
    if ($imagen === false) {
        header("Location: receta_form.php?error=imagen_invalida");
        exit;
    }
    try {
        $stmt = $pdo->prepare("INSERT INTO RECETA (id_usuario, titulo, descripcion, ingredientes, instrucciones, tiempo_preparacion, imagen)
                               VALUES (:u, :t, :d, :i, :ins, :tp, :img)");
        $stmt->execute([
            ':u' => $_SESSION['id_usuario'], ':t' => $d['titulo'], ':d' => $d['descripcion'],
            ':i' => $d['ingredientes'], ':ins' => $d['instrucciones'], ':tp' => $d['tiempo'], ':img' => $imagen,
        ]);
        header("Location: receta_detalle.php?id=" . $pdo->lastInsertId());
    } catch (PDOException $e) {
        borrar_imagen($imagen);
        header("Location: receta_form.php?error=error_servidor");
    }
    exit;
}

// ==========================================
// EDITAR RECETA
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $accion === 'editar') {
    $id = (int)($_POST['id_receta'] ?? 0);
    $stmt = $pdo->prepare("SELECT * FROM RECETA WHERE id_receta = :id");
    $stmt->execute([':id' => $id]);
    $receta = $stmt->fetch();
    if (!$receta || !puede_editar($receta['id_usuario'])) {
        header("Location: recetas.php");
        exit;
    }

    $d = datos_receta();
    if ($d['titulo'] === '' || $d['descripcion'] === '' || $d['ingredientes'] === '' || $d['instrucciones'] === '' || $d['tiempo'] < 1) {
        header("Location: receta_form.php?id=$id&error=campos_incompletos");
        exit;
    }
    $imagen = subir_imagen($_FILES['imagen'] ?? null);
    if ($imagen === false) {
        header("Location: receta_form.php?id=$id&error=imagen_invalida");
        exit;
    }
    try {
        $stmt = $pdo->prepare("UPDATE RECETA SET titulo=:t, descripcion=:d, ingredientes=:i, instrucciones=:ins,
                               tiempo_preparacion=:tp, imagen=:img WHERE id_receta=:id");
        $stmt->execute([
            ':t' => $d['titulo'], ':d' => $d['descripcion'], ':i' => $d['ingredientes'],
            ':ins' => $d['instrucciones'], ':tp' => $d['tiempo'],
            ':img' => $imagen ?? $receta['imagen'], ':id' => $id,
        ]);
        if ($imagen !== null) borrar_imagen($receta['imagen']);
        header("Location: receta_detalle.php?id=$id&msg=editada");
    } catch (PDOException $e) {
        borrar_imagen($imagen);
        header("Location: receta_form.php?id=$id&error=error_servidor");
    }
    exit;
}

// ==========================================
// ELIMINAR RECETA
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $accion === 'eliminar') {
    $id = (int)($_POST['id_receta'] ?? 0);
    $stmt = $pdo->prepare("SELECT * FROM RECETA WHERE id_receta = :id");
    $stmt->execute([':id' => $id]);
    $receta = $stmt->fetch();
    if ($receta && puede_editar($receta['id_usuario'])) {
        $pdo->prepare("DELETE FROM RECETA WHERE id_receta = :id")->execute([':id' => $id]);
        borrar_imagen($receta['imagen']);
        header("Location: recetas.php?msg=eliminada");
    } else {
        header("Location: recetas.php");
    }
    exit;
}

header("Location: recetas.php");
exit;
