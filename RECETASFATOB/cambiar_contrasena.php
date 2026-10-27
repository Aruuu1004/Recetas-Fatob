<?php
session_start();
require_once "includes/helpers.php";
requiere_login();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Cambiar contraseña - RECETAS FATOB</title>
    <link rel="stylesheet" href="styles/general.css?v=5">
    <?php include "views/favicon.php"; ?>
</head>
<body>

    <?php include "views/navbar.php"; ?>

    <main class="auth">
        <section class="auth-card">
            <h1>Cambiar contraseña</h1>
            <p class="sub">Por seguridad, primero ingresá tu contraseña actual.</p>

            <?php if (isset($_GET['msg']) && $_GET['msg'] === 'cambiada'): ?>
                <div class="alert success">Tu contraseña se actualizó correctamente.</div>
            <?php endif; ?>

            <?php if (isset($_GET['error'])): ?>
                <div class="alert error">
                    <?php
                        if ($_GET['error'] === 'campos_incompletos') echo "Completá todos los campos.";
                        if ($_GET['error'] === 'contrasena_actual_incorrecta') echo "La contraseña actual no es correcta.";
                        if ($_GET['error'] === 'contrasenas_no_coinciden') echo "Las contraseñas nuevas no coinciden.";
                        if ($_GET['error'] === 'contrasena_corta') echo "La nueva contraseña debe tener al menos 8 caracteres.";
                        if ($_GET['error'] === 'contrasena_igual') echo "La nueva contraseña tiene que ser distinta de la actual.";
                        if ($_GET['error'] === 'error_servidor') echo "Ocurrió un error en el servidor, intentalo más tarde.";
                    ?>
                </div>
            <?php endif; ?>

            <form class="auth-form" action="contrasenaController.php?accion=cambiar" method="POST">
                <div class="campo">
                    <input type="password" id="contrasena_actual" name="contrasena_actual" placeholder="Contraseña actual" aria-label="Contraseña actual" autocomplete="current-password" required>
                </div>
                <div class="campo">
                    <input type="password" id="contrasena_nueva" name="contrasena_nueva" minlength="8" placeholder="Nueva contraseña (mínimo 8 caracteres)" aria-label="Nueva contraseña" autocomplete="new-password" required>
                </div>
                <div class="campo">
                    <input type="password" id="confirmar_contrasena" name="confirmar_contrasena" minlength="8" placeholder="Verificar nueva contraseña" aria-label="Verificar nueva contraseña" autocomplete="new-password" required>
                </div>

                <label class="check"><input type="checkbox" id="verContrasena" data-mostrar="contrasena_actual,contrasena_nueva,confirmar_contrasena"> Mostrar contraseñas</label>

                <button type="submit" class="btn">Cambiar contraseña</button>
            </form>

            <p class="auth-pie"><a href="perfil.php">Volver a mi perfil</a></p>
        </section>
    </main>

    <?php include "views/footer.php"; ?>
    <script src="js/auth.js"></script>
    <script>
    (function () {
        var p1 = document.getElementById('contrasena_nueva');
        var p2 = document.getElementById('confirmar_contrasena');
        function chequear() {
            p2.setCustomValidity(p2.value && p1.value !== p2.value ? 'Las contraseñas no coinciden' : '');
        }
        p1.addEventListener('input', chequear);
        p2.addEventListener('input', chequear);
    })();
    </script>
</body>
</html>
