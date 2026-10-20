<?php /* Tarjeta chica de receta favorita (perfil). Espera la variable $f con id_receta, titulo e imagen (y opcionalmente tiempo_preparacion, promedio, votos). */ ?>
<article class="card">
    <a class="card-img" href="receta_detalle.php?id=<?= (int)$f['id_receta'] ?>" tabindex="-1" aria-hidden="true">
        <?php if (!empty($f['imagen'])): ?>
            <img src="img/recetas/<?= e($f['imagen']) ?>" alt="" loading="lazy">
        <?php else: ?>
            <div class="sin-imagen">🍽️</div>
        <?php endif; ?>
    </a>
    <div class="card-body">
        <h3><a href="receta_detalle.php?id=<?= (int)$f['id_receta'] ?>"><?= e($f['titulo']) ?></a></h3>
        <?php if (isset($f['tiempo_preparacion'])): ?>
            <div class="card-datos">
                <span><?= icono_reloj() ?> <?= (int)$f['tiempo_preparacion'] ?> min</span>
                <span><?= icono_estrella() ?> <?= number_format((float)($f['promedio'] ?? 0), 1) ?> (<?= (int)($f['votos'] ?? 0) ?>)</span>
            </div>
        <?php endif; ?>
        <a class="ver-mas" href="receta_detalle.php?id=<?= (int)$f['id_receta'] ?>">Ver receta</a>
    </div>
</article>
