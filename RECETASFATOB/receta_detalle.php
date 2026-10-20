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

$s = $pdo->prepare("SELECT c.*, u.nombre, u.avatar, cal.puntuacion
                    FROM COMENTARIO c
                    JOIN USUARIO u ON u.id_usuario = c.id_usuario
                    LEFT JOIN CALIFICACION cal ON cal.id_receta = c.id_receta AND cal.id_usuario = c.id_usuario
                    WHERE c.id_receta = :r ORDER BY c.fecha DESC");
$s->execute([':r' => $id]);
$comentarios = $s->fetchAll();

$ingredientes = array_values(array_filter(array_map('trim', preg_split('/\R/', $r['ingredientes']))));
$pasos = pasos_receta($r['instrucciones']);
$fotos = imagenes_receta($pdo, $r);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($r['titulo']) ?> - RECETAS FATOB</title>
    <link rel="stylesheet" href="styles/general.css?v=5">
    <?php include "views/favicon.php"; ?>
</head>
<body>
    <?php include "views/navbar.php"; ?>

    <main class="container container-ancho detalle">
        <?php if (isset($_GET['msg']) && $_GET['msg'] === 'editada'): ?>
            <p class="msg ok">Receta actualizada.</p>
        <?php endif; ?>

        <a class="volver" href="recetas.php" aria-label="Volver a las recetas" title="Volver a las recetas">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M15 5l-7 7 7 7" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </a>

        <section class="receta-portada">
            <?php if ($fotos): ?>
                <div class="carousel">
                    <div class="carousel-track">
                        <?php foreach ($fotos as $ft): ?>
                            <div class="carousel-item"><img src="img/recetas/<?= e($ft['archivo']) ?>" alt="<?= e($r['titulo']) ?>"></div>
                        <?php endforeach; ?>
                    </div>
                    <button type="button" class="carousel-btn carousel-prev" aria-label="Foto anterior">&#8249;</button>
                    <button type="button" class="carousel-btn carousel-next" aria-label="Foto siguiente">&#8250;</button>
                    <div class="carousel-dots"></div>
                </div>
            <?php else: ?>
                <div class="portada-img sin-imagen">🍽️</div>
            <?php endif; ?>
            <h1 class="receta-titulo"><?= e($r['titulo']) ?></h1>
            <p class="receta-meta">
                <span>Por <a href="perfil.php?id=<?= (int)$r['id_usuario'] ?>" style="color:inherit;text-decoration:underline"><?= e($r['autor']) ?></a></span>
                <span><?= (int)$r['tiempo_preparacion'] ?> min</span>
                <span><span class="stars" style="color:var(--crema)"><?= estrellas($r['promedio']) ?></span> <?= number_format($r['promedio'], 1) ?> (<?= (int)$r['votos'] ?> votos)</span>
            </p>
        </section>

        <?php if (trim($r['descripcion']) !== ''): ?>
            <p class="receta-desc"><?= e($r['descripcion']) ?></p>
        <?php endif; ?>

        <?php $hayAcciones = logueado() || puede_editar($r['id_usuario']); ?>
        <?php if ($hayAcciones): ?>
            <div class="receta-acciones">
                <?php if (logueado()): $esFav = es_favorita($pdo, $r['id_receta']); ?>
                    <form action="favoritoController.php" method="POST">
                        <input type="hidden" name="id_receta" value="<?= (int)$r['id_receta'] ?>">
                        <button type="submit"><?= $esFav ? '♥ En mis favoritas' : '♡ Agregar a favoritas' ?></button>
                    </form>
                <?php endif; ?>
                <?php if (puede_editar($r['id_usuario'])): ?>
                    <a class="boton boton-claro" href="receta_form.php?id=<?= (int)$r['id_receta'] ?>">Editar</a>
                    <form action="recetasController.php?accion=eliminar" method="POST" onsubmit="return confirm('¿Eliminar esta receta?');">
                        <input type="hidden" name="id_receta" value="<?= (int)$r['id_receta'] ?>">
                        <button type="submit" class="peligro">Eliminar</button>
                    </form>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div class="receta-cuerpo">
            <section aria-labelledby="tit-instrucciones">
                <div class="barra-titulo"><h3 id="tit-instrucciones">Instrucciones</h3></div>
                <div class="pasos">
                    <?php foreach ($pasos as $i => $p): ?>
                        <article class="paso">
                            <div class="paso-cab">
                                <span class="paso-num"><?= $i + 1 ?></span>
                                <h4><?= e($p['titulo']) ?></h4>
                            </div>
                            <p><?= e($p['texto']) ?></p>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>

            <aside class="comentarios-panel" id="comentarios" aria-label="Comentarios y calificaciones">
                <h3>Comentarios y Calificaciones (<?= count($comentarios) ?>)</h3>

                <div class="comentarios-lista">
                    <?php if (!$comentarios): ?>
                        <p class="sin-comentarios">Todavía no hay comentarios. ¡Sé la primera persona en opinar!</p>
                    <?php endif; ?>
                    <?php foreach ($comentarios as $c): ?>
                        <div class="coment">
                            <div class="coment-avatar"><?= avatar_interior($c['avatar'] ?? '', $c['nombre']) ?></div>
                            <div>
                                <div class="coment-nombre"><?= e($c['nombre']) ?><span class="coment-fecha"><?= e(date('d/m/Y', strtotime($c['fecha']))) ?></span></div>
                                <p class="coment-texto"><?= nl2br(e($c['texto'])) ?></p>
                            </div>
                            <?php if (!empty($c['puntuacion'])): ?>
                                <div class="coment-estrellas" aria-label="<?= (int)$c['puntuacion'] ?> de 5 estrellas"><?= str_repeat('★', (int)$c['puntuacion']) ?><?= str_repeat('☆', 5 - (int)$c['puntuacion']) ?></div>
                            <?php endif; ?>
                            <?php if (puede_editar($c['id_usuario'])): ?>
                                <form class="coment-borrar" action="comentarioController.php?accion=eliminar" method="POST" onsubmit="return confirm('¿Eliminar comentario?');">
                                    <input type="hidden" name="id_comentario" value="<?= (int)$c['id_comentario'] ?>">
                                    <input type="hidden" name="id_receta" value="<?= (int)$r['id_receta'] ?>">
                                    <button type="submit">Eliminar</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>

                <?php if (logueado()): ?>
                    <form class="comentar-form" action="comentarioController.php?accion=crear" method="POST">
                        <input type="hidden" name="id_receta" value="<?= (int)$r['id_receta'] ?>">
                        <label for="texto">Dejanos tu comentario y calificación</label>
                        <textarea id="texto" name="texto" rows="3" maxlength="1000" placeholder="Escribí tu comentario..."></textarea>
                        <div class="estrellas-input" role="radiogroup" aria-label="Tu calificación">
                            <?php for ($i = 5; $i >= 1; $i--): ?>
                                <input type="radio" id="nota<?= $i ?>" name="puntuacion" value="<?= $i ?>" <?= $miNota === $i ? 'checked' : '' ?>>
                                <label for="nota<?= $i ?>" title="<?= $i ?> <?= $i === 1 ? 'estrella' : 'estrellas' ?>">★</label>
                            <?php endfor; ?>
                        </div>
                        <button type="submit"><?= $miNota ? 'Actualizar' : 'Enviar' ?></button>
                    </form>
                <?php else: ?>
                    <p class="comentar-aviso"><a href="login.php"><strong>Iniciá sesión</strong></a> para comentar y calificar esta receta.</p>
                <?php endif; ?>
            </aside>
        </div>

        <section class="ingredientes-bloque" aria-labelledby="tit-ingredientes">
            <h3 id="tit-ingredientes">Ingredientes</h3>
            <ul class="ingredientes-fila">
                <?php foreach ($ingredientes as $ing): ?>
                    <li class="ing" title="<?= e($ing) ?>">
                        <span class="ing-icono" aria-hidden="true"><?= emoji_ingrediente($ing) ?></span>
                        <span class="ing-nombre"><?= e(nombre_corto_ingrediente($ing)) ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        </section>
    </main>

    <?php include "views/footer.php"; ?>
</body>
</html>
