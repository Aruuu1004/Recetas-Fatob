<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Registro - RECETAS FATOB</title>
    <link rel="stylesheet" href="styles/general.css?v=5">
    <?php include "views/favicon.php"; ?>
</head>
<body>

    <?php include "views/navbar.php"; ?>

    <main class="auth">
        <section class="auth-card">
            <h1>Creá tu cuenta y empezá a cocinar</h1>
            <p class="sub">Guardá tus recetas favoritas y compartí las tuyas.</p>

            <?php if (isset($_GET['error'])): ?>
                <div class="alert error">
                    <?php
                        if ($_GET['error'] === 'campos_incompletos') echo "Completá todos los campos requeridos.";
                        if ($_GET['error'] === 'email_existente') echo "El correo electrónico ya está registrado.";
                        if ($_GET['error'] === 'contrasenas_no_coinciden') echo "Las contraseñas no coinciden.";
                        if ($_GET['error'] === 'contrasena_corta') echo "La contraseña debe tener al menos 8 caracteres.";
                        if ($_GET['error'] === 'error_servidor') echo "Ocurrió un error en el servidor, intentalo más tarde.";
                    ?>
                </div>
            <?php endif; ?>

            <form class="auth-form" action="usuarioController.php?accion=registro" method="POST">
                <div class="campo">
                    <input type="text" id="nombre" name="nombre" placeholder="Nombre de usuario" aria-label="Nombre de usuario" autocomplete="username" required>
                </div>

                <div class="campo">
                    <input type="email" id="email" name="email" placeholder="Correo Electrónico" aria-label="Correo electrónico" autocomplete="email" required>
                </div>

                <div class="campo">
                    <input type="password" id="contrasena" name="contrasena" minlength="8" placeholder="Contraseña (mínimo 8 caracteres)" aria-label="Contraseña" autocomplete="new-password" required>
                </div>

                <div class="campo">
                    <input type="password" id="confirmar_contrasena" name="confirmar_contrasena" minlength="8" placeholder="Verificar contraseña" aria-label="Verificar contraseña" autocomplete="new-password" required>
                </div>

                <label class="check"><input type="checkbox" id="verContrasena" data-mostrar="contrasena,confirmar_contrasena"> Mostrar contraseña</label>

                <fieldset class="restricciones">
                    <legend>Restricciones alimentarias / preferencias (opcional)</legend>
                    <div class="chips">
                        <label class="chip"><input type="checkbox" name="restricciones[]" value="1"> Celiaco</label>
                        <label class="chip"><input type="checkbox" name="restricciones[]" value="2"> Intolerante a la lactosa</label>
                        <label class="chip"><input type="checkbox" name="restricciones[]" value="3"> Vegano</label>
                        <label class="chip"><input type="checkbox" name="restricciones[]" value="4"> Vegetariano</label>
                    </div>
                </fieldset>

                <button type="submit" class="btn">Registrarse</button>
            </form>

            <p class="auth-pie">¿Ya tenés cuenta? <a href="login.php">Iniciá sesión</a></p>
        </section>
    </main>

    <?php include "views/footer.php"; ?>
    <script src="js/auth.js"></script>

    <script>
    // Avisa en el momento si las dos contraseñas no coinciden
    (function () {
        var p1 = document.getElementById('contrasena');
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
