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
    if (contar_subidas($_FILES['imagenes'] ?? null) > MAX_FOTOS_RECETA) {
        header("Location: receta_form.php?error=demasiadas_imagenes");
        exit;
    }
    $nuevas = subir_imagenes($_FILES['imagenes'] ?? null);
    if ($nuevas === false) {
        header("Location: receta_form.php?error=imagen_invalida");
        exit;
    }
    try {
        $pdo->beginTransaction();
        // RECETA.imagen guarda la primera foto: es la portada que se ve en las tarjetas
        $stmt = $pdo->prepare("INSERT INTO RECETA (id_usuario, titulo, descripcion, ingredientes, instrucciones, tiempo_preparacion, imagen)
                               VALUES (:u, :t, :d, :i, :ins, :tp, :img)");
        $stmt->execute([
            ':u' => $_SESSION['id_usuario'], ':t' => $d['titulo'], ':d' => $d['descripcion'],
            ':i' => $d['ingredientes'], ':ins' => $d['instrucciones'], ':tp' => $d['tiempo'], ':img' => $nuevas[0] ?? null,
        ]);
        $idNueva = $pdo->lastInsertId();
        $ins = $pdo->prepare("INSERT INTO RECETA_IMAGEN (id_receta, archivo, orden) VALUES (:r, :a, :o)");
        foreach ($nuevas as $orden => $archivo) {
            $ins->execute([':r' => $idNueva, ':a' => $archivo, ':o' => $orden]);
        }
        $pdo->commit();
        header("Location: receta_detalle.php?id=" . $idNueva);
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        foreach ($nuevas as $n) borrar_imagen($n);
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

    // Fotos actuales, cuáles se quitan y cuántas se suman (el total no puede pasar el máximo)
    $fotos  = imagenes_receta($pdo, $receta, true);
    $idsAct = array_map('intval', array_column($fotos, 'id'));
    $quitar = array_values(array_intersect(array_map('intval', (array)($_POST['quitar'] ?? [])), $idsAct));
    $quedan = count($fotos) - count($quitar);
    if ($quedan + contar_subidas($_FILES['imagenes'] ?? null) > MAX_FOTOS_RECETA) {
        header("Location: receta_form.php?id=$id&error=demasiadas_imagenes");
        exit;
    }
    $nuevas = subir_imagenes($_FILES['imagenes'] ?? null);
    if ($nuevas === false) {
        header("Location: receta_form.php?id=$id&error=imagen_invalida");
        exit;
    }

    try {
        $pdo->beginTransaction();
        $pdo->prepare("UPDATE RECETA SET titulo=:t, descripcion=:d, ingredientes=:i, instrucciones=:ins, tiempo_preparacion=:tp
                       WHERE id_receta=:id")
            ->execute([':t' => $d['titulo'], ':d' => $d['descripcion'], ':i' => $d['ingredientes'],
                       ':ins' => $d['instrucciones'], ':tp' => $d['tiempo'], ':id' => $id]);

        $archivosQuitados = [];
        $del = $pdo->prepare("DELETE FROM RECETA_IMAGEN WHERE id_imagen = :i AND id_receta = :r");
        foreach ($fotos as $ft) {
            if (in_array((int)$ft['id'], $quitar, true)) {
                $del->execute([':i' => $ft['id'], ':r' => $id]);
                $archivosQuitados[] = $ft['archivo'];
            }
        }

        $stmtOrden = $pdo->prepare("SELECT COALESCE(MAX(orden), -1) + 1 FROM RECETA_IMAGEN WHERE id_receta = :r");
        $stmtOrden->execute([':r' => $id]);
        $orden = (int)$stmtOrden->fetchColumn();
        $ins = $pdo->prepare("INSERT INTO RECETA_IMAGEN (id_receta, archivo, orden) VALUES (:r, :a, :o)");
        foreach ($nuevas as $archivo) {
            $ins->execute([':r' => $id, ':a' => $archivo, ':o' => $orden++]);
        }

        // La portada de las tarjetas es la primera foto que quede
        $stmtPortada = $pdo->prepare("SELECT archivo FROM RECETA_IMAGEN WHERE id_receta = :r ORDER BY orden, id_imagen LIMIT 1");
        $stmtPortada->execute([':r' => $id]);
        $portada = $stmtPortada->fetchColumn() ?: null;
        $pdo->prepare("UPDATE RECETA SET imagen = :img WHERE id_receta = :id")->execute([':img' => $portada, ':id' => $id]);

        $pdo->commit();
        foreach ($archivosQuitados as $a) borrar_imagen($a);
        header("Location: receta_detalle.php?id=$id&msg=editada");
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        foreach ($nuevas as $n) borrar_imagen($n);
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
        // Se juntan los archivos antes de borrar (las filas de RECETA_IMAGEN se borran solas con la receta)
        $archivos = array_column(imagenes_receta($pdo, $receta), 'archivo');
        if (!empty($receta['imagen'])) $archivos[] = $receta['imagen'];
        $pdo->prepare("DELETE FROM RECETA WHERE id_receta = :id")->execute([':id' => $id]);
        foreach (array_unique($archivos) as $a) borrar_imagen($a);
        header("Location: recetas.php?msg=eliminada");
    } else {
        header("Location: recetas.php");
    }
    exit;
}

header("Location: recetas.php");
exit;
