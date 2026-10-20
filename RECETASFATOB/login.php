<?php session_start(); ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Iniciar Sesión - RECETAS FATOB</title>
    <link rel="stylesheet" href="styles/general.css?v=5">
    <?php include "views/favicon.php"; ?>
</head>
<body>

    <?php include "views/navbar.php"; ?>

    <main class="auth">
        <section class="auth-card">
            <h1>¡Bienvenido!</h1>
            <p class="sub">Inicia sesión para seguir cocinando.</p>

            <?php if (isset($_GET['registro']) && $_GET['registro'] === 'exito'): ?>
                <div class="alert success">¡Cuenta creada con éxito! Ya podés ingresar.</div>
            <?php endif; ?>

            <?php if (isset($_GET['logout']) && $_GET['logout'] === 'exito'): ?>
                <div class="alert success">Sesión cerrada correctamente.</div>
            <?php endif; ?>

            <?php if (isset($_GET['error'])): ?>
                <div class="alert error">
                    <?php
                        if ($_GET['error'] === 'credenciales_invalidas') echo "Correo electrónico o contraseña incorrectos.";
                        if ($_GET['error'] === 'campos_incompletos') echo "Por favor completá todos los campos.";
                        if ($_GET['error'] === 'error_servidor') echo "Error de conexión, intentalo más tarde.";
                        if ($_GET['error'] === 'acceso_denegado') echo "Iniciá sesión para entrar a esa sección.";
                    ?>
                </div>
            <?php endif; ?>

            <form class="auth-form" action="usuarioController.php?accion=login" method="POST">
                <div class="campo">
                    <input type="email" id="email" name="email" placeholder="Correo Electrónico" aria-label="Correo electrónico" autocomplete="email" required>
                </div>

                <div class="campo">
                    <input type="password" id="contrasena" name="contrasena" placeholder="Contraseña" aria-label="Contraseña" autocomplete="current-password" required>
                </div>

                <label class="check"><input type="checkbox" id="verContrasena" data-mostrar="contrasena"> Mostrar contraseña</label>

                <button type="submit" class="btn">Iniciar Sesión</button>
            </form>

            <a class="auth-olvido" href="mailto:estudios.ariadna@gmail.com?subject=Recuperar%20mi%20contrase%C3%B1a%20-%20RECETAS%20FATOB">¿Olvidaste tu contraseña?</a>
            <p class="auth-pie">¿No tenés cuenta? <a href="registro.php">Registrate</a></p>
        </section>
    </main>

    <?php include "views/footer.php"; ?>
    <script src="js/auth.js"></script>
</body>
</html>
