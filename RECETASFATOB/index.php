<?php
session_start();
require_once "includes/config.php";
require_once "includes/helpers.php";

$stmt =$pdo->query("SELECT r.*, u.nombre AS autor,
                            COALESCE(AVG(c.puntuacion), 0) AS promedio, COUNT(c.id_calificacion) AS votos
                     FROM RECETA r
                     JOIN USUARIO u ON u.id_usuario = r.id_usuario
                     LEFT JOIN CALIFICACION c ON c.id_receta = r.id_receta
                     GROUP BY r.id_receta, u.nombre
                     ORDER BY r.fecha_creacion DESC LIMIT 6");
$recetas =$stmt->fetchAll();
$misFavoritas = ids_favoritas($pdo);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Inicio - RECETAS FATOB</title>
    <link rel="stylesheet" href="styles/general.css">
    <?php include "views/favicon.php"; ?>
</head>
<body>
    <?php include "views/navbar.php"; ?>

    <main class="container">
        <section class="hero">
            <h1>Bienvenido a RECETAS FATOB</h1>
            <p>Descubrí, compartí y calificá recetas.</p>
            
            <!-- BUSCADOR DE USUARIOS CON BOTÓN -->
            <div class="buscador-usuarios-container" style="margin: 20px auto; position: relative; max-width: 450px;">
                <form id="searchForm" style="display: flex; gap: 8px;">
                    <input type="text" id="searchInput" placeholder="Buscar usuarios o cocineros..." style="flex: 1; padding: 10px; border: 1px solid #ccc; border-radius: 4px;">
                    <button type="submit" id="searchBtn" class="boton" style="padding: 10px 20px; margin: 0;">Buscar</button>
                </form>
                <div id="searchResults" style="position: absolute; width: 100%; background: #fff; border: 1px solid #ccc; border-top: none; z-index: 10; border-radius: 0 0 4px 4px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);"></div>
            </div>

            <a class="boton" href="recetas.php">Ver todas las recetas</a>
        </section>

        <h2>Últimas recetas</h2>
        <?php if (!$recetas): ?>
            <p>Todavía no hay recetas. <a href="receta_form.php">¡Subí la primera!</a></p>
        <?php else: ?>
            <div class="grid">
                <?php foreach ($recetas as$r) include "views/receta_card.php"; ?>
            </div>
        <?php endif; ?>
    </main>

    <?php include "views/footer.php"; ?>

    <script>
    // Lógica exclusiva para la búsqueda de usuarios en el index
    document.addEventListener("DOMContentLoaded", () => {
        const searchForm = document.getElementById("searchForm");
        const inputBusqueda = document.getElementById("searchInput");
        const resultadosDiv = document.getElementById("searchResults");

        function ejecutarBusqueda() {
            let query = inputBusqueda.value.trim();

            if (query.length < 2) {
                resultadosDiv.innerHTML = "<div style='padding: 10px; color: #777;'>Escribe al menos 2 caracteres</div>";
                return;
            }

            fetch(`usuarioController.php?accion=buscar&q=${encodeURIComponent(query)}`)
                .then(response => {
                    if (!response.ok) {
                        return response.text().then(text => { throw new Error(text || response.statusText); });
                    }
                    return response.json();
                })
                .then(data => {
                    let html = "";
                    if (data && data.length > 0) {
                        data.forEach(user => {
                            let userId = user.id_usuario || user.id;
                            
                            html += `<div style="padding: 10px; border-bottom: 1px solid #eee; text-align: left;">
                                <a href="perfil.php?id=${userId}" style="text-decoration: none; color: #333; font-weight: bold;">${user.nombre}</a>
                            </div>`;
                        });
                    } else {
                        html = "<div style='padding: 10px; color: #777;'>No se encontraron usuarios</div>";
                    }
                    resultadosDiv.innerHTML = html;
                })
                .catch(error => {
                    console.error("Error en la búsqueda:", error);
                    resultadosDiv.innerHTML = `<div style='padding: 10px; color: red; font-size: 12px;'>Error en la búsqueda</div>`;
                });
        }

        if (searchForm) {
            searchForm.addEventListener("submit", function(e) {
                e.preventDefault();
                ejecutarBusqueda();
            });
        }
    });
    </script>
</body>
</html>