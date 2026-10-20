<?php
session_start();
require_once "includes/config.php";
require_once "includes/helpers.php";

$base = "SELECT r.*, u.nombre AS autor,
                COALESCE(AVG(c.puntuacion), 0) AS promedio, COUNT(c.id_calificacion) AS votos
         FROM RECETA r
         JOIN USUARIO u ON u.id_usuario = r.id_usuario
         LEFT JOIN CALIFICACION c ON c.id_receta = r.id_receta
         GROUP BY r.id_receta, u.nombre";

// Recetas del día: 3 recetas que cambian solas cada día
$recetasDia = $pdo->query($base . " ORDER BY MD5(CONCAT(r.id_receta, CURDATE())) LIMIT 3")->fetchAll();

// Las más valoradas
$masValoradas = $pdo->query($base . " ORDER BY promedio DESC, votos DESC, r.fecha_creacion DESC LIMIT 10")->fetchAll();

$misFavoritas = ids_favoritas($pdo);

// Foto del encabezado: la primera receta del día que tenga imagen
$fotoHero = null;
foreach ($recetasDia as $rd) { if (!empty($rd['imagen'])) { $fotoHero = $rd; break; } }

$saludo = '¡Hola!';
if (logueado()) {
    $primerNombre = explode(' ', trim($_SESSION['nombre'] ?? ''))[0];
    if ($primerNombre !== '') $saludo = '¡Hola, ' . e($primerNombre) . '!';
}

// Categorías: llevan al listado filtrado por esa palabra
$categorias = [
    ['Platos principales', 'principal', '<path d="M6 40h36M10 40a14 14 0 0 1 28 0M24 22v-4M20 18h8"/>'],
    ['Ensaladas', 'ensalada', '<path d="M6 22h36a18 18 0 0 1-36 0zM14 22c0-6 5-10 10-10M24 22c2-7 8-10 13-8M30 22c0-4 3-6 6-6"/>'],
    ['Carnes', 'carne', '<path d="M8 26c-2-9 6-16 16-16 11 0 18 7 16 16-2 9-10 13-19 12-8-1-12-5-13-12zM28 22c4 0 7 3 6 7-1 3-5 5-9 4"/>'],
    ['Pastas', 'pasta', '<path d="M6 26h36a18 14 0 0 1-36 0zM12 26c0-8 4-14 12-14s12 6 12 14M18 26c0-5 2-9 6-9M24 8v6"/>'],
    ['Postres', 'postre', '<path d="M12 26h24l-3 14H15zM12 26c-4-2-4-8 0-10 0-6 8-8 12-4 4-4 12-2 12 4 4 2 4 8 0 10M24 26v14"/>'],
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Inicio - RECETAS FATOB</title>
    <link rel="stylesheet" href="styles/general.css?v=5">
    <?php include "views/favicon.php"; ?>
</head>
<body>
    <?php include "views/navbar.php"; ?>

    <main class="container">
        <section class="saludo">
            <div class="saludo-texto">
                <h1><?= $saludo ?></h1>
                <p>Descubrí, cociná y disfrutá la vida con las mejores recetas.</p>

                <div class="busca">
                    <form class="busca-form" id="searchForm" action="recetas.php" method="GET" role="search">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="10.5" cy="10.5" r="6.5" fill="none" stroke="currentColor" stroke-width="2.2"/><path d="M15.5 15.5L21 21" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/></svg>
                        <input type="search" id="searchInput" name="q" placeholder="Buscar recetas, ingredientes o categorías..." aria-label="Buscar">
                        <button type="submit" id="searchBtn">Buscar</button>
                    </form>
                    <div class="busca-modo" role="group" aria-label="Qué querés buscar">
                        <button type="button" data-modo="recetas" aria-pressed="true">Recetas</button>
                        <button type="button" data-modo="cocineros" aria-pressed="false">Cocineros</button>
                    </div>
                    <div id="searchResults" aria-live="polite"></div>
                </div>
            </div>
            <div class="saludo-foto">
                <?php if ($fotoHero): ?>
                    <img src="img/recetas/<?= e($fotoHero['imagen']) ?>" alt="<?= e($fotoHero['titulo']) ?>">
                <?php else: ?>
                    <div class="sin-imagen">🍳</div>
                <?php endif; ?>
            </div>
        </section>

        <?php if (!$recetasDia): ?>
            <p class="vacio-msg">Todavía no hay recetas. <a href="receta_form.php"><strong>¡Subí la primera!</strong></a></p>
        <?php else: ?>
            <h2 class="sec-titulo">Recetas del día</h2>
            <div class="grid" style="margin-bottom:34px">
                <?php foreach ($recetasDia as $r) include "views/receta_card.php"; ?>
            </div>

            <h2 class="sec-titulo">Categorías</h2>
            <div class="categorias">
                <?php foreach ($categorias as $c): ?>
                    <a class="cat" href="recetas.php?q=<?= urlencode($c[1]) ?>">
                        <svg viewBox="0 0 48 48" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><?= $c[2] ?></svg>
                        <?= e($c[0]) ?>
                    </a>
                <?php endforeach; ?>
            </div>

            <h2 class="sec-titulo">Las más valoradas</h2>
            <div class="carousel rank">
                <div class="carousel-track">
                    <?php foreach ($masValoradas as $r): ?>
                        <div class="carousel-item"><?php include "views/receta_card.php"; ?></div>
                    <?php endforeach; ?>
                </div>
                <button type="button" class="carousel-btn carousel-prev" aria-label="Anteriores">&#8249;</button>
                <button type="button" class="carousel-btn carousel-next" aria-label="Siguientes">&#8250;</button>
            </div>
        <?php endif; ?>
    </main>

    <?php include "views/footer.php"; ?>

    <script>
    // Buscador del inicio: busca recetas (va al listado) o cocineros (resultados en un desplegable)
    document.addEventListener("DOMContentLoaded", () => {
        const form = document.getElementById("searchForm");
        const input = document.getElementById("searchInput");
        const resultados = document.getElementById("searchResults");
        const botonesModo = document.querySelectorAll(".busca-modo button");
        let modo = "recetas";

        function esc(t) { const d = document.createElement("div"); d.textContent = t; return d.innerHTML; }

        botonesModo.forEach(b => b.addEventListener("click", () => {
            modo = b.dataset.modo;
            botonesModo.forEach(x => x.setAttribute("aria-pressed", x === b ? "true" : "false"));
            input.placeholder = modo === "recetas" ? "Buscar recetas, ingredientes o categorías..." : "Buscar cocineros por nombre...";
            input.name = modo === "recetas" ? "q" : "";
            resultados.innerHTML = "";
            input.focus();
        }));

        function buscarCocineros() {
            const q = input.value.trim();
            if (q.length < 2) { resultados.innerHTML = "<div class='res vacio'>Escribí al menos 2 caracteres</div>"; return; }
            fetch(`usuarioController.php?accion=buscar&q=${encodeURIComponent(q)}`)
                .then(r => r.ok ? r.json() : r.text().then(t => { throw new Error(t || r.statusText); }))
                .then(data => {
                    if (!data || !data.length) { resultados.innerHTML = "<div class='res vacio'>No se encontraron cocineros</div>"; return; }
                    resultados.innerHTML = data.map(u => {
                        const id = u.id_usuario || u.id;
                        return `<div class="res"><a href="perfil.php?id=${encodeURIComponent(id)}">${esc(u.nombre)}</a></div>`;
                    }).join("");
                })
                .catch(err => { console.error("Error en la búsqueda:", err); resultados.innerHTML = "<div class='res vacio'>No se pudo buscar. Intentá de nuevo.</div>"; });
        }

        form.addEventListener("submit", e => {
            if (modo === "cocineros") { e.preventDefault(); buscarCocineros(); }
        });
        document.addEventListener("click", e => { if (!e.target.closest(".busca")) resultados.innerHTML = ""; });
    });
    </script>
</body>
</html>
