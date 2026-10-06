<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$logueado = isset($_SESSION['id_usuario']);
$esAdmin  = $logueado && (int)($_SESSION['id_rol'] ?? 0) === 1;
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
