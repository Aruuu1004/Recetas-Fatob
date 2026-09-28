<?php session_start(); ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Iniciar Sesión - RECETAS FATOB</title>
    <link rel="stylesheet" href="../styles/ejemplo.css">
</head>
<body>

    <?php include "navbar.php"; ?>

    <main class="container">
        <h2>Iniciar Sesión</h2>

        <?php if (isset($_GET['registro']) && $_GET['registro'] === 'exito'): ?>
            <div class="alert success" style="color: green;">
                ¡Cuenta creada con éxito! Ya podés ingresar.
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['logout']) && $_GET['logout'] === 'exito'): ?>
            <div class="alert success" style="color: blue;">
                Sesión cerrada correctamente.
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['error'])): ?>
            <div class="alert error" style="color: red;">
                <?php
                    if ($_GET['error'] === 'credenciales_invalidas') echo "Correo electrónico o contraseña incorrectos.";
                    if ($_GET['error'] === 'campos_incompletos') echo "Por favor completá todos los campos.";
                    if ($_GET['error'] === 'error_servidor') echo "Error de conexión, intentalo más tarde.";
                ?>
            </div>
        <?php endif; ?>

        <form action="../usuarioController.php?accion=login" method="POST">
            <div class="form-group">
                <label for="email">Correo Electrónico:</label>
                <input type="email" id="email" name="email" required>
            </div>

            <div class="form-group">
                <label for="contrasena">Contraseña:</label>
                <input type="password" id="contrasena" name="contrasena" required>
            </div>

            <button type="submit" class="btn">Ingresar</button>
        </form>
    </main>

    <?php include "footer.php"; ?>

</body>
</html>