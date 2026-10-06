<?php
session_start();
require_once "includes/config.php";
require_once "includes/helpers.php";
requiere_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $idReceta   = (int)($_POST['id_receta'] ?? 0);
    $puntuacion = (int)($_POST['puntuacion'] ?? 0);

    if ($idReceta > 0 && $puntuacion >= 1 && $puntuacion <= 5) {
        try {
            // Una sola nota por usuario y receta: si ya existe, se actualiza
            $stmt = $pdo->prepare("INSERT INTO CALIFICACION (id_receta, id_usuario, puntuacion)
                                   VALUES (:r, :u, :p)
                                   ON DUPLICATE KEY UPDATE puntuacion = VALUES(puntuacion)");
            $stmt->execute([':r' => $idReceta, ':u' => $_SESSION['id_usuario'], ':p' => $puntuacion]);
        } catch (PDOException $e) {
            // receta inexistente u otro error: se vuelve igual al detalle
        }
    }
    header("Location: receta_detalle.php?id=$idReceta");
    exit;
}
header("Location: recetas.php");
exit;
