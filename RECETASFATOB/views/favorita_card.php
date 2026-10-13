<?php /* Tarjeta chica de receta favorita (perfil). Espera la variable $f con id_receta, titulo e imagen. */ ?>
<article class="fav-card carousel-item">
    <?php if (!empty($f['imagen'])): ?>
        <img src="img/recetas/<?= e($f['imagen']) ?>" alt="<?= e($f['titulo']) ?>">
    <?php else: ?>
        <div class="sin-imagen">🍽️</div>
    <?php endif; ?>
    <h4><?= e($f['titulo']) ?></h4>
    <a class="ver-mas" href="receta_detalle.php?id=<?= (int)$f['id_receta'] ?>">Ver más</a>
</article>
