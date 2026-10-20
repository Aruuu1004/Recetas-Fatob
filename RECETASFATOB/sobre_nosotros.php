<?php
session_start();
require_once "includes/helpers.php";

$equipo = [
    ['Francesca Rodriguez', 'Back-end', 'Desarrolladora back end'],
    ['Ariadna Miguel',      'Visual',   'Desarrolladora Visual'],
    ['Thomas Giacomucci',   'Código',   'Programador'],
    ['Oscar Mayta',         'Front-end','Desarrollador Front-End'],
    ['Bruno Orozco',        'Diseño',   'Diseñador Estructural'],
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sobre nosotros - RECETAS FATOB</title>
    <link rel="stylesheet" href="styles/general.css?v=5">
    <?php include "views/favicon.php"; ?>
</head>
<body>
    <?php include "views/navbar.php"; ?>

    <main class="container container-ancho">
        <h2 class="equipo-titulo">¡Conocé al equipo de Recetas FATOB!</h2>

        <section class="equipo" aria-label="Integrantes del equipo">
            <?php foreach ($equipo as $m): ?>
                <article class="miembro">
                    <div class="nav-avatar"><?= avatar_silueta() ?></div>
                    <h3><?= e($m[0]) ?></h3>
                    <span class="rol"><?= e($m[1]) ?></span>
                    <p><?= e($m[2]) ?></p>
                </article>
            <?php endforeach; ?>
        </section>

        <section class="acerca">
            <h2>¿De qué trata FATOB?</h2>
            <p>Somos un grupo de 5 amigos que compartimos la misma pasión: la cocina. Creemos que la mejor forma de disfrutar la vida es con buenas recetas, buena compañía y un toque de humor.</p>

            <h2>¿Cuál es el objetivo de esta página?</h2>
            <p>Nuestro objetivo es conectar a personas que disfrutan cocinar y ofrecer un espacio donde puedan inspirarse y aprender unos de otros.</p>

            <h2>¿A quién está dirigida nuestra página?</h2>
            <p>Está dirigida a todas las personas que quieran aprender a cocinar, descubrir nuevos platos o compartir sus propias creaciones.</p>

            <h2>¿Qué tipos de recetas se pueden encontrar?</h2>
            <p>Nuestra idea es reunir recetas variadas, desde comidas sencillas para todos los días hasta postres y platillos más elaborados.</p>

            <div class="acciones">
                <a class="boton" href="recetas.php">Explorar recetas</a>
                <?php if (logueado()): ?>
                    <a class="boton boton-claro" href="receta_form.php">Publicar una receta</a>
                <?php else: ?>
                    <a class="boton boton-claro" href="registro.php">Sumarme a la comunidad</a>
                <?php endif; ?>
            </div>
        </section>
    </main>

    <?php include "views/footer.php"; ?>
</body>
</html>
