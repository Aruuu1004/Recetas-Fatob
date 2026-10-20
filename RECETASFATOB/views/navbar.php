<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$logueado = isset($_SESSION['id_usuario']);
$esAdmin  = $logueado && (int)($_SESSION['id_rol'] ?? 0) === 1;
$paginaActual = basename($_SERVER['SCRIPT_NAME'] ?? '');

// Si no existe la variable en sesión, el banner debe mostrarse
$mostrarBannerCookies = !isset($_SESSION['cookies_aceptadas']);

// Foto del usuario para el botón de la esquina (si no tiene, se muestra la silueta)
$navAvatarHtml = '';
if ($logueado) {
    require_once __DIR__ . '/../includes/helpers.php';
    if (!isset($_SESSION['nav_avatar_cache'])) {
        try {
            require_once __DIR__ . '/../includes/config.php';
            $navStmt = $pdo->prepare("SELECT avatar FROM USUARIO WHERE id_usuario = :id");
            $navStmt->execute([':id' => $_SESSION['id_usuario']]);
            $navAvatarArchivo = (string)$navStmt->fetchColumn();
        } catch (Throwable $ex) { $navAvatarArchivo = ''; }
    } else {
        $navAvatarArchivo = $_SESSION['nav_avatar_cache'];
    }
    $navAvatarHtml = avatar_interior($navAvatarArchivo, $_SESSION['nombre'] ?? '');
}
function nav_activo($archivos, $actual) { return in_array($actual, (array)$archivos, true) ? ' activo' : ''; }
?>
<nav class="navbar" id="navbar">
    <div class="navbar-in">
        <a class="brand" href="index.php"><img class="logo" src="img/logo.png" alt=""><span>RECETAS FATOB</span></a>

        <button type="button" class="nav-toggle" id="navToggle" aria-label="Abrir menú" aria-expanded="false" aria-controls="navMenu">
            <span></span><span></span><span></span>
        </button>

        <div class="nav-menu" id="navMenu">
            <ul class="nav-links">
                <li><a class="<?= trim(nav_activo('index.php', $paginaActual)) ?>" href="index.php">Inicio</a></li>
                <li><a class="<?= trim(nav_activo(['recetas.php', 'receta_detalle.php', 'receta_form.php'], $paginaActual)) ?>" href="recetas.php">Recetas</a></li>
                <li><a class="<?= trim(nav_activo('sobre_nosotros.php', $paginaActual)) ?>" href="sobre_nosotros.php">Sobre Nosotros</a></li>
            </ul>

            <div class="nav-acciones">
                <?php if ($logueado): ?>
                    <details class="nav-user">
                        <summary aria-label="Menú de mi cuenta">
                            <span class="nav-avatar"><?= $navAvatarHtml ?></span>
                            <span class="nav-nombre"><?= htmlspecialchars($_SESSION['nombre'] ?? '') ?></span>
                        </summary>
                        <ul class="nav-user-menu">
                            <li><a href="perfil.php">Mi perfil</a></li>
                            <li><a href="perfil.php?seccion=favoritas">Mis recetas favoritas</a></li>
                            <li><a href="receta_form.php">Nueva receta</a></li>
                            <?php if ($esAdmin): ?>
                                <li class="sep"></li>
                                <li><a href="usuario.php">Admin usuarios</a></li>
                                <li><a href="admin_recetas.php">Admin recetas</a></li>
                            <?php endif; ?>
                            <li class="sep"></li>
                            <li><a href="usuarioController.php?accion=logout">Cerrar sesión</a></li>
                        </ul>
                    </details>
                <?php else: ?>
                    <a class="nav-btn-link<?= $paginaActual === 'registro.php' ? ' destacado' : '' ?>" href="registro.php">Registrarse</a>
                    <a class="nav-btn-link<?= $paginaActual === 'login.php' ? ' destacado' : '' ?>" href="login.php">Iniciar Sesión</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>
<script src="js/navbar.js"></script>

<!-- BANNER DE COOKIES BASADO EN SESIÓN -->
<?php if ($mostrarBannerCookies): ?>
<div id="cookieBanner" role="region" aria-label="Aviso de cookies">
    <span>Esta página utiliza cookies para mejorar tu experiencia de navegación.</span>
    <button type="button" id="btnAceptarCookies">Aceptar</button>
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
