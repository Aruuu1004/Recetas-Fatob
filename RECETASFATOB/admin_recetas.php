<?php
session_start();
require_once "includes/config.php";
require_once "includes/helpers.php";

// Solo el administrador (rol 1) puede ver este panel
if (!es_admin()) {
    header("Location: login.php?error=acceso_denegado");
    exit;
}

$q = trim($_GET['q'] ?? '');
$sql = "SELECT r.id_receta, r.titulo, r.tiempo_preparacion, r.fecha_creacion, u.nombre AS autor,
               COALESCE(AVG(c.puntuacion), 0) AS promedio, COUNT(c.id_calificacion) AS votos
        FROM RECETA r
        JOIN USUARIO u ON u.id_usuario = r.id_usuario
        LEFT JOIN CALIFICACION c ON c.id_receta = r.id_receta";
$params = [];
if ($q !== '') {
    $sql .= " WHERE r.titulo LIKE :q1 OR u.nombre LIKE :q2";
    $params = [':q1' => "%$q%", ':q2' => "%$q%"];
}
$sql .= " GROUP BY r.id_receta, u.nombre ORDER BY r.id_receta DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$recetas = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Gestión de recetas - RECETAS FATOB</title>
    <link rel="stylesheet" href="styles/general.css?v=5">
    <?php include "views/favicon.php"; ?>
</head>
<body>
    <?php include "views/navbar.php"; ?>

    <main class="container">
        <div class="admin-encabezado">
            <h2>Gestión de recetas</h2>
            <a class="boton" href="receta_form.php">+ Agregar receta</a>
        </div>

        <?php if (($_GET['msg'] ?? '') === 'eliminada'): ?>
            <p class="msg ok">Receta eliminada correctamente.</p>
        <?php endif; ?>

        <form class="admin-filtro" action="admin_recetas.php" method="GET">
            <input type="search" name="q" placeholder="Filtrar por título o autor..." value="<?= e($q) ?>">
            <button type="submit">Filtrar</button>
            <?php if ($q !== ''): ?><a class="boton" href="admin_recetas.php">Limpiar</a><?php endif; ?>
        </form>

        <?php if (!$recetas): ?>
            <p>No hay recetas<?= $q !== '' ? ' que coincidan con el filtro' : '' ?>.</p>
        <?php else: ?>
            <div class="tabla-scroll">
                <table class="tabla-admin">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Título</th>
                            <th>Autor</th>
                            <th>Tiempo</th>
                            <th>Calificación</th>
                            <th>Creada</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recetas as $r): $id = (int)$r['id_receta']; ?>
                            <tr>
                                <td data-label="ID"><?= $id ?></td>
                                <td data-label="Título"><?= e($r['titulo']) ?></td>
                                <td data-label="Autor"><?= e($r['autor']) ?></td>
                                <td data-label="Tiempo"><?= (int)$r['tiempo_preparacion'] ?> min</td>
                                <td data-label="Calificación">
                                    <?= $r['votos'] > 0 ? estrellas($r['promedio']) . ' (' . (int)$r['votos'] . ')' : 'Sin votos' ?>
                                </td>
                                <td data-label="Creada"><?= e(substr($r['fecha_creacion'], 0, 10)) ?></td>
                                <td data-label="Acciones" class="acciones">
                                    <a class="btn-ver" href="receta_detalle.php?id=<?= $id ?>">Ver</a>
                                    <a class="btn-editar" href="receta_form.php?id=<?= $id ?>">Editar</a>
                                    <form action="recetasController.php?accion=eliminar" method="POST" onsubmit="return confirm('¿Eliminar esta receta?');">
                                        <input type="hidden" name="id_receta" value="<?= $id ?>">
                                        <input type="hidden" name="volver" value="admin">
                                        <button type="submit" class="btn-eliminar">Eliminar</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </main>

    <?php include "views/footer.php"; ?>
</body>
</html>
