<?php
session_start();
require_once "includes/config.php";
require_once "includes/helpers.php";

$q = trim($_GET['q'] ?? '');
$sql = "SELECT r.*, u.nombre AS autor,
               COALESCE(AVG(c.puntuacion), 0) AS promedio, COUNT(c.id_calificacion) AS votos
        FROM RECETA r
        JOIN USUARIO u ON u.id_usuario = r.id_usuario
        LEFT JOIN CALIFICACION c ON c.id_receta = r.id_receta";
$params = [];
if ($q !== '') {
    $sql .= " WHERE r.titulo LIKE :q1 OR r.descripcion LIKE :q2 OR r.ingredientes LIKE :q3";
    $like = "%$q%";
    $params = [':q1' => $like, ':q2' => $like, ':q3' => $like];
}
$sql .= " GROUP BY r.id_receta, u.nombre ORDER BY r.fecha_creacion DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$recetas = $stmt->fetchAll();
$misFavoritas = ids_favoritas($pdo);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Recetas - RECETAS FATOB</title>
    <link rel="stylesheet" href="styles/general.css?v=5">
    <?php include "views/favicon.php"; ?>
</head>
<body>
    <?php include "views/navbar.php"; ?>

    <main class="container">
        <div class="listado-cab">
            <h2><?= $q !== '' ? 'Resultados para "' . e($q) . '"' : 'Todas las recetas' ?></h2>
            <div class="busca">
                <form class="busca-form" action="recetas.php" method="GET" role="search">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="10.5" cy="10.5" r="6.5" fill="none" stroke="currentColor" stroke-width="2.2"/><path d="M15.5 15.5L21 21" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/></svg>
                    <input type="search" name="q" value="<?= e($q) ?>" placeholder="Buscar recetas, ingredientes o categorías..." aria-label="Buscar recetas">
                    <button type="submit">Buscar</button>
                </form>
            </div>
        </div>

        <?php if (isset($_GET['msg']) && $_GET['msg'] === 'eliminada'): ?>
            <p class="msg ok">Receta eliminada correctamente.</p>
        <?php endif; ?>

        <?php if (!$recetas): ?>
            <p class="vacio-msg">No se encontraron recetas<?= $q !== '' ? ' con esa búsqueda. Probá con otra palabra o <a href="recetas.php"><strong>mirá todas las recetas</strong></a>' : '' ?>.</p>
        <?php else: ?>
            <div class="grid grid-4">
                <?php foreach ($recetas as $r) include "views/receta_card.php"; ?>
            </div>
        <?php endif; ?>
    </main>

    <?php include "views/footer.php"; ?>
</body>
</html>
