<?php
session_start();
require_once "includes/config.php";
require_once "includes/helpers.php";
requiere_login();

$accion   = $_GET['accion'] ?? '';
$idReceta = (int)($_POST['id_receta'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $accion === 'crear') {
    $texto = trim($_POST['texto'] ?? '');
    if ($idReceta > 0 && $texto !== '') {
        try {
            $stmt = $pdo->prepare("INSERT INTO COMENTARIO (id_receta, id_usuario, texto) VALUES (:r, :u, :t)");
            $stmt->execute([':r' => $idReceta, ':u' => $_SESSION['id_usuario'], ':t' => mb_substr($texto, 0, 1000)]);
        } catch (PDOException $e) {
        }
    }
    header("Location: receta_detalle.php?id=$idReceta#comentarios");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $accion === 'eliminar') {
    $idComentario = (int)($_POST['id_comentario'] ?? 0);
    $stmt = $pdo->prepare("SELECT id_usuario FROM COMENTARIO WHERE id_comentario = :id");
    $stmt->execute([':id' => $idComentario]);
    $autor = $stmt->fetchColumn();
    if ($autor !== false && puede_editar($autor)) {
        $pdo->prepare("DELETE FROM COMENTARIO WHERE id_comentario = :id")->execute([':id' => $idComentario]);
    }
    header("Location: receta_detalle.php?id=$idReceta#comentarios");
    exit;
}

header("Location: recetas.php");
exit;
