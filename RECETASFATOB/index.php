<?php
session_start();
require_once "includes/config.php";
require_once "includes/helpers.php";

$stmt = $pdo->query("SELECT r.*, u.nombre AS autor,
                            COALESCE(AVG(c.puntuacion), 0) AS promedio, COUNT(c.id_calificacion) AS votos
                     FROM RECETA r
                     JOIN USUARIO u ON u.id_usuario = r.id_usuario
                     LEFT JOIN CALIFICACION c ON c.id_receta = r.id_receta
                     GROUP BY r.id_receta, u.nombre
                     ORDER BY r.fecha_creacion DESC LIMIT 6");
$recetas = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Inicio - RECETAS FATOB</title>
    <link rel="stylesheet" href="styles/general.css">
</head>
<body>
    <?php include "views/navbar.php"; ?>

    <main class="container">
        <section class="hero">
            <h1>Bienvenido a RECETAS FATOB</h1>
            <p>Descubrí, compartí y calificá recetas.</p>
            <a class="boton" href="recetas.php">Ver todas las recetas</a>
        </section>

        <h2>Últimas recetas</h2>
        <?php if (!$recetas): ?>
            <p>Todavía no hay recetas. <a href="receta_form.php">¡Subí la primera!</a></p>
        <?php else: ?>
            <div class="grid">
                <?php foreach ($recetas as $r) include "views/receta_card.php"; ?>
            </div>
        <?php endif; ?>
    </main>

    <?php include "views/footer.php"; ?>
</body>
</html>
