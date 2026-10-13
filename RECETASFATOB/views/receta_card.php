<?php /* Tarjeta de receta. Espera la variable $r con los datos de la receta. */ ?>
<article class="card">
    <?php if (logueado()): $esFav = in_array((int)$r['id_receta'], $misFavoritas ?? [], true); ?>
        <form class="fav-form" action="favoritoController.php" method="POST">
            <input type="hidden" name="id_receta" value="<?= (int)$r['id_receta'] ?>">
            <button type="submit" class="fav-btn" title="<?= $esFav ? 'Quitar de favoritas' : 'Agregar a favoritas' ?>"
                    aria-label="<?= $esFav ? 'Quitar de favoritas' : 'Agregar a favoritas' ?>"><?= $esFav ? '♥' : '♡' ?></button>
        </form>
    <?php endif; ?>
    <?php if (!empty($r['imagen'])): ?>
        <img src="img/recetas/<?= e($r['imagen']) ?>" alt="<?= e($r['titulo']) ?>">
    <?php else: ?>
        <div class="sin-imagen">🍽️</div>
    <?php endif; ?>
    <div class="card-body">
        <h3><a href="receta_detalle.php?id=<?= (int)$r['id_receta'] ?>"><?= e($r['titulo']) ?></a></h3>
        <p class="meta">Por <?= e($r['autor']) ?> · ⏱ <?= (int)$r['tiempo_preparacion'] ?> min</p>
        <p class="stars"><?= estrellas($r['promedio']) ?> <small>(<?= (int)$r['votos'] ?>)</small></p>
        <p><?= e(mb_strimwidth($r['descripcion'], 0, 100, '…')) ?></p>
        <a class="ver-mas" href="receta_detalle.php?id=<?= (int)$r['id_receta'] ?>">Ver más</a>
    </div>
</article>
