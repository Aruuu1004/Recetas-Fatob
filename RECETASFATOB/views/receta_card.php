<?php /* Tarjeta de receta. Espera la variable $r con los datos de la receta. */ ?>
<article class="card">
    <?php if (logueado()): $esFav = in_array((int)$r['id_receta'], $misFavoritas ?? [], true); ?>
        <form class="fav-form" action="favoritoController.php" method="POST">
            <input type="hidden" name="id_receta" value="<?= (int)$r['id_receta'] ?>">
            <button type="submit" class="fav-btn" title="<?= $esFav ? 'Quitar de favoritas' : 'Agregar a favoritas' ?>"
                    aria-label="<?= $esFav ? 'Quitar de favoritas' : 'Agregar a favoritas' ?>"><?= $esFav ? '♥' : '♡' ?></button>
        </form>
    <?php endif; ?>
    <a class="card-img" href="receta_detalle.php?id=<?= (int)$r['id_receta'] ?>" tabindex="-1" aria-hidden="true">
        <?php if (!empty($r['imagen'])): ?>
            <img src="img/recetas/<?= e($r['imagen']) ?>" alt="" loading="lazy">
        <?php else: ?>
            <div class="sin-imagen">🍽️</div>
        <?php endif; ?>
    </a>
    <div class="card-body">
        <h3><a href="receta_detalle.php?id=<?= (int)$r['id_receta'] ?>"><?= e($r['titulo']) ?></a></h3>
        <div class="card-datos">
            <span><?= icono_reloj() ?> <?= (int)$r['tiempo_preparacion'] ?> min</span>
            <span><?= icono_estrella() ?> <?= number_format($r['promedio'], 1) ?> (<?= (int)$r['votos'] ?>)</span>
        </div>
        <a class="ver-mas" href="receta_detalle.php?id=<?= (int)$r['id_receta'] ?>">Ver receta</a>
    </div>
</article>
