<?php /* Tarjeta de receta. Espera la variable $r con los datos de la receta. */ ?>
<article class="card">
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
