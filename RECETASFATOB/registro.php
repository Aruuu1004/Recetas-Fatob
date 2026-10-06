<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registro - RECETAS FATOB</title>
    <link rel="stylesheet" href="styles/general.css">

</head>
<body>

    <?php include "views/navbar.php"; ?>

    <main class="container">
        <h2>Crear Cuenta</h2>

        <?php if (isset($_GET['error'])): ?>
            <div class="alert error">
                <?php
                    if ($_GET['error'] === 'campos_incompletos') echo "Completá todos los campos requeridos.";
                    if ($_GET['error'] === 'email_existente') echo "El correo electrónico ya está registrado.";
                    if ($_GET['error'] === 'error_servidor') echo "Ocurrió un error en el servidor, intentalo más tarde.";
                ?>
            </div>
        <?php endif; ?>

        <form action="usuarioController.php?accion=registro" method="POST">
            <div class="form-group">
                <label for="nombre">Nombre de usuario:</label>
                <input type="text" id="nombre" name="nombre" required>
            </div>

            <div class="form-group">
                <label for="email">Correo Electrónico:</label>
                <input type="email" id="email" name="email" required>
            </div>

            <div class="form-group">
                <label for="contrasena">Contraseña:</label>
                <input type="password" id="contrasena" name="contrasena" minlength="8" placeholder="Mínimo 8 caracteres" required>
            </div>

            <div class="form-group">
                <label>Restricciones Alimentarias / Preferencias:</label>
                <label class="check"><input type="checkbox" name="restricciones[]" value="1"> Celiaco</label>
                <label class="check"><input type="checkbox" name="restricciones[]" value="2"> Intolerante a la Lactosa</label>
                <label class="check"><input type="checkbox" name="restricciones[]" value="3"> Vegano</label>
                <label class="check"><input type="checkbox" name="restricciones[]" value="4"> Vegetariano</label>
            </div>

            <button type="submit" class="btn">Registrarse</button>
        </form>
    </main>

    <?php include "views/footer.php"; ?>

</body>
</html>
