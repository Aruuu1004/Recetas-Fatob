<?php
session_start();
require_once "includes/config.php";
require_once "includes/helpers.php";

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT r.*, u.nombre AS autor,
                              COALESCE(AVG(c.puntuacion), 0) AS promedio, COUNT(c.id_calificacion) AS votos
                       FROM RECETA r
                       JOIN USUARIO u ON u.id_usuario = r.id_usuario
                       LEFT JOIN CALIFICACION c ON c.id_receta = r.id_receta
                       WHERE r.id_receta = :id
                       GROUP BY r.id_receta, u.nombre");
$stmt->execute([':id' => $id]);
$r = $stmt->fetch();
if (!$r) {
    header("Location: recetas.php");
    exit;
}

$miNota = 0;
if (logueado()) {
    $s = $pdo->prepare("SELECT puntuacion FROM CALIFICACION WHERE id_receta = :r AND id_usuario = :u");
    $s->execute([':r' => $id, ':u' => $_SESSION['id_usuario']]);
    $miNota = (int)$s->fetchColumn();
}

$s = $pdo->prepare("SELECT c.*, u.nombre FROM COMENTARIO c JOIN USUARIO u ON u.id_usuario = c.id_usuario
                    WHERE c.id_receta = :r ORDER BY c.fecha DESC");
$s->execute([':r' => $id]);
$comentarios = $s->fetchAll();

$ingredientes = array_filter(array_map('trim', preg_split('/\R/', $r['ingredientes'])));
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($r['titulo']) ?> - RECETAS FATOB</title>
    <link rel="stylesheet" href="styles/general.css">
</head>
<body>
    <?php include "views/navbar.php"; ?>

    <main class="container detalle">
        <?php if (isset($_GET['msg']) && $_GET['msg'] === 'editada'): ?>
            <p class="msg ok">Receta actualizada.</p>
        <?php endif; ?>

        <h2><?= e($r['titulo']) ?></h2>
        <p class="meta">Por <?= e($r['autor']) ?> · ⏱ <?= (int)$r['tiempo_preparacion'] ?> min ·
            <span class="stars"><?= estrellas($r['promedio']) ?></span>
            <?= number_format($r['promedio'], 1) ?> (<?= (int)$r['votos'] ?> votos)</p>

        <?php if ($r['imagen']): ?>
            <img class="portada" src="img/recetas/<?= e($r['imagen']) ?>" alt="<?= e($r['titulo']) ?>">
        <?php endif; ?>

        <p><?= e($r['descripcion']) ?></p>

        <?php if (puede_editar($r['id_usuario'])): ?>
            <div class="acciones">
                <a class="boton" href="receta_form.php?id=<?= (int)$r['id_receta'] ?>">Editar</a>
                <form action="recetasController.php?accion=eliminar" method="POST" onsubmit="return confirm('¿Eliminar esta receta?');">
                    <input type="hidden" name="id_receta" value="<?= (int)$r['id_receta'] ?>">
                    <button type="submit" class="peligro">Eliminar</button>
                </form>
            </div>
        <?php endif; ?>

        <h3>Ingredientes</h3>
        <ul>
            <?php foreach ($ingredientes as $ing): ?><li><?= e($ing) ?></li><?php endforeach; ?>
        </ul>

        <h3>Preparación</h3>
        <p><?= nl2br(e($r['instrucciones'])) ?></p>

        <h3>Calificación</h3>
        <?php if (logueado()): ?>
            <form class="inline" action="calificacionController.php" method="POST">
                <input type="hidden" name="id_receta" value="<?= (int)$r['id_receta'] ?>">
                <select name="puntuacion" required>
                    <option value="">Elegí una nota</option>
                    <?php for ($i = 5; $i >= 1; $i--): ?>
                        <option value="<?= $i ?>" <?= $miNota === $i ? 'selected' : '' ?>><?= $i ?> - <?= estrellas($i) ?></option>
                    <?php endfor; ?>
                </select>
                <button type="submit"><?= $miNota ? 'Cambiar mi nota' : 'Calificar' ?></button>
            </form>
        <?php else: ?>
            <p><a href="login.php">Iniciá sesión</a> para calificar esta receta.</p>
        <?php endif; ?>

        <h3 id="comentarios">Comentarios (<?= count($comentarios) ?>)</h3>
        <?php if (logueado()): ?>
            <form class="formulario" action="comentarioController.php?accion=crear" method="POST">
                <input type="hidden" name="id_receta" value="<?= (int)$r['id_receta'] ?>">
                <textarea name="texto" rows="3" maxlength="1000" placeholder="Escribí tu comentario..." required></textarea>
                <button type="submit">Comentar</button>
            </form>
        <?php else: ?>
            <p><a href="login.php">Iniciá sesión</a> para comentar.</p>
        <?php endif; ?>

        <?php foreach ($comentarios as $c): ?>
            <div class="comentario">
                <strong><?= e($c['nombre']) ?></strong> <small><?= e($c['fecha']) ?></small>
                <p><?= nl2br(e($c['texto'])) ?></p>
                <?php if (puede_editar($c['id_usuario'])): ?>
                    <form action="comentarioController.php?accion=eliminar" method="POST" onsubmit="return confirm('¿Eliminar comentario?');">
                        <input type="hidden" name="id_comentario" value="<?= (int)$c['id_comentario'] ?>">
                        <input type="hidden" name="id_receta" value="<?= (int)$r['id_receta'] ?>">
                        <button type="submit" class="peligro chico">Eliminar</button>
                    </form>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </main>

    <?php include "views/footer.php"; ?>
</body>
</html>
