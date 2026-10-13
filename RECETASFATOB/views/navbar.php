<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$logueado = isset($_SESSION['id_usuario']);
$esAdmin  = $logueado && (int)($_SESSION['id_rol'] ?? 0) === 1;

// Si no existe la variable en sesión, el banner debe mostrarse
$mostrarBannerCookies = !isset($_SESSION['cookies_aceptadas']);
?>
<nav class="navbar">
    <a class="brand" href="index.php"><img class="logo" src="img/logo.png" alt="">RECETAS FATOB</a>
    <ul class="nav-links">
        <li><a href="index.php">Inicio</a></li>
        <li><a href="recetas.php">Recetas</a></li>
        <?php if ($logueado): ?>
            <li><a href="receta_form.php">Nueva receta</a></li>
            <li><a href="perfil.php">Mi perfil</a></li>
            <?php if ($esAdmin): ?>
                <li><a href="usuario.php">Usuarios</a></li>
            <?php endif; ?>
            <li><a href="usuarioController.php?accion=logout">Cerrar sesión (<?= htmlspecialchars($_SESSION['nombre']) ?>)</a></li>
        <?php else: ?>
            <li><a href="login.php">Iniciar sesión</a></li>
            <li><a href="registro.php">Registrarse</a></li>
        <?php endif; ?>
    </ul>
    <form class="buscador" action="recetas.php" method="GET">
        <input type="search" name="q" placeholder="Buscar recetas..." value="<?= htmlspecialchars($_GET['q'] ?? '') ?>">
        <button type="submit">Buscar</button>
    </form>
</nav>

<!-- BANNER DE COOKIES BASADO EN SESIÓN -->
<?php if ($mostrarBannerCookies): ?>
<div id="cookieBanner" style="position: fixed; bottom: 0; left: 0; width: 100%; background: #222; color: #fff; padding: 15px; text-align: center; z-index: 9999; box-shadow: 0 -2px 5px rgba(0,0,0,0.3);">
    <span>Esta página utiliza cookies para mejorar tu experiencia de navegación.</span> 
    <button id="btnAceptarCookies" style="background: #ff6600; color: #fff; border: none; padding: 6px 15px; cursor: pointer; margin-left: 10px; border-radius: 4px; font-weight: bold;">Aceptar</button>
</div>

<script>
document.addEventListener("DOMContentLoaded", () => {
    const btnAceptar = document.getElementById("btnAceptarCookies");

    if (btnAceptar) {
        btnAceptar.addEventListener("click", function() {
            // Hacemos una petición rápida o recargamos informando al servidor que aceptó las cookies
            fetch('usuarioController.php?accion=aceptar_cookies')
                .then(() => {
                    location.reload();
                });
        });
    }
});
</script>
<?php endif; ?>