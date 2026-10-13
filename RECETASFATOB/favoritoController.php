<?php
session_start();
require_once "includes/config.php";
require_once "includes/helpers.php";
requiere_login();

// Agrega o quita una receta de favoritas (si ya estaba, la quita)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $idReceta  = (int)($_POST['id_receta'] ?? 0);
    $idUsuario = (int)$_SESSION['id_usuario'];

    if ($idReceta > 0) {
        try {
            $del = $pdo->prepare("DELETE FROM FAVORITO WHERE id_usuario = :u AND id_receta = :r");
            $del->execute([':u' => $idUsuario, ':r' => $idReceta]);
            if ($del->rowCount() === 0) {
                $ins = $pdo->prepare("INSERT INTO FAVORITO (id_usuario, id_receta) VALUES (:u, :r)");
                $ins->execute([':u' => $idUsuario, ':r' => $idReceta]);
            }
        } catch (PDOException $e) {
            // receta inexistente u otro error: se vuelve igual a la página anterior
        }
    }

    // Volver a la página desde la que se hizo clic (solo si es de este mismo sitio)
    $destino = 'recetas.php';
    $ref = $_SERVER['HTTP_REFERER'] ?? '';
    if ($ref !== '') {
        $p = parse_url($ref);
        $hostActual = explode(':', $_SERVER['HTTP_HOST'] ?? '')[0];
        if (($p['host'] ?? '') === $hostActual && isset($p['path'])) {
            $destino = $p['path'] . (isset($p['query']) ? '?' . $p['query'] : '');
        }
    }
    header("Location: " . $destino);
    exit;
}

header("Location: recetas.php");
exit;
