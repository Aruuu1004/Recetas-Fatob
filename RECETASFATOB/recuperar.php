<?php session_start(); ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Recuperar contraseña - RECETAS FATOB</title>
    <link rel="stylesheet" href="styles/general.css?v=5">
    <?php include "views/favicon.php"; ?>
</head>
<body>

    <?php include "views/navbar.php"; ?>

    <main class="auth">
        <section class="auth-card">
            <h1>¿Olvidaste tu contraseña?</h1>
            <p class="sub">Ingresá tu correo y te enviamos un código de 6 números para crear una nueva.</p>

            <?php if (isset($_GET['error'])): ?>
                <div class="alert error">
                    <?php
                        if ($_GET['error'] === 'email_invalido') echo "Ingresá un correo electrónico válido.";
                        if ($_GET['error'] === 'error_servidor') echo "Ocurrió un error en el servidor, intentalo más tarde.";
                    ?>
                </div>
            <?php endif; ?>

            <form class="auth-form" action="contrasenaController.php?accion=solicitar" method="POST">
                <div class="campo">
                    <input type="email" id="email" name="email" placeholder="Correo Electrónico" aria-label="Correo electrónico" autocomplete="email" required>
                </div>
                <button type="submit" class="btn">Enviar código</button>
            </form>

            <p class="auth-pie"><a href="login.php">Volver a iniciar sesión</a></p>
        </section>
    </main>

    <?php include "views/footer.php"; ?>
    <script src="js/auth.js"></script>
</body>
</html>
