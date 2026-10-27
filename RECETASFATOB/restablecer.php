<?php
session_start();

// Si no pasó por "recuperar.php" (no hay correo en la sesión), se vuelve al primer paso
if (empty($_SESSION['recup_email'])) {
    header("Location: recuperar.php");
    exit;
}
require_once "includes/recuperacion.php";

// Solo en localhost: código de prueba (en producción llega por correo)
$devCodigo = $_SESSION['dev_codigo_recuperacion'] ?? null;

// Segundos que faltan para poder pedir otro código (el control real está en contrasenaController.php; esto es solo visual)
$ultimoPedido = (int)($_SESSION['recup_pedidos'][strtolower($_SESSION['recup_email'])] ?? 0);
$faltan = max(0, RECUP_ESPERA_SEG - (time() - $ultimoPedido));
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Nueva contraseña - RECETAS FATOB</title>
    <link rel="stylesheet" href="styles/general.css?v=5">
    <?php include "views/favicon.php"; ?>
</head>
<body>

    <?php include "views/navbar.php"; ?>

    <main class="auth">
        <section class="auth-card">
            <h1>Creá tu nueva contraseña</h1>
            <p class="sub">Si ese correo está registrado, te enviamos un código de 6 números. Vale por 10 minutos.</p>

            <?php if ($devCodigo): ?>
                <div class="alert">Tu código de verificación es <strong><?= htmlspecialchars($devCodigo) ?></strong></div>
            <?php endif; ?>

            <?php if (($_GET['aviso'] ?? '') === 'espera'): ?>
                <div class="alert">Ya te enviamos un código hace poco. Revisá tu correo o esperá un momento para pedir otro.</div>
            <?php endif; ?>

            <?php if (isset($_GET['error'])): ?>
                <div class="alert error">
                    <?php
                        if ($_GET['error'] === 'campos_incompletos') echo "Completá todos los campos.";
                        if ($_GET['error'] === 'codigo_invalido') echo "El código es incorrecto o venció. Si hace falta, pedí uno nuevo.";
                        if ($_GET['error'] === 'contrasenas_no_coinciden') echo "Las contraseñas no coinciden.";
                        if ($_GET['error'] === 'contrasena_igual') echo "La nueva contraseña tiene que ser distinta de la actual.";
                        if ($_GET['error'] === 'contrasena_corta') echo "La contraseña debe tener al menos 8 caracteres.";
                        if ($_GET['error'] === 'error_servidor') echo "Ocurrió un error en el servidor, intentalo más tarde.";
                    ?>
                </div>
            <?php endif; ?>

            <form class="auth-form" action="contrasenaController.php?accion=restablecer" method="POST">
                <div class="campo">
                    <input type="text" id="codigo" name="codigo" inputmode="numeric" pattern="[0-9 ]{6,7}" maxlength="7" placeholder="Código de 6 números" aria-label="Código de 6 números" autocomplete="one-time-code" required>
                </div>
                <div class="campo">
                    <input type="password" id="contrasena" name="contrasena" minlength="8" placeholder="Nueva contraseña (mínimo 8 caracteres)" aria-label="Nueva contraseña" autocomplete="new-password" required>
                </div>
                <div class="campo">
                    <input type="password" id="confirmar_contrasena" name="confirmar_contrasena" minlength="8" placeholder="Verificar contraseña" aria-label="Verificar contraseña" autocomplete="new-password" required>
                </div>

                <label class="check"><input type="checkbox" id="verContrasena" data-mostrar="contrasena,confirmar_contrasena"> Mostrar contraseña</label>

                <button type="submit" class="btn">Guardar contraseña</button>
            </form>

            <p class="auth-pie" id="reenvio" data-faltan="<?= (int)$faltan ?>">
                <?php if ($faltan > 0): ?>
                    ¿No te llegó? Podés pedir otro código en <strong id="cuenta"><?= (int)$faltan ?></strong> s.
                    <noscript><a href="recuperar.php">Pedir otro código</a></noscript>
                <?php else: ?>
                    ¿No te llegó? <a href="recuperar.php">Pedir otro código</a>
                <?php endif; ?>
            </p>
        </section>
    </main>

    <?php include "views/footer.php"; ?>
    <script src="js/auth.js"></script>
    <script>
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
    <script>
    // Cuenta regresiva para poder pedir otro código; al llegar a 0 aparece el enlace
    (function () {
        var cont = document.getElementById('reenvio');
        var num = document.getElementById('cuenta');
        if (!cont || !num) return;
        var fin = Date.now() + parseInt(cont.getAttribute('data-faltan'), 10) * 1000;
        var t = setInterval(function () {
            var resto = Math.ceil((fin - Date.now()) / 1000);
            if (resto > 0) { num.textContent = resto; return; }
            clearInterval(t);
            cont.innerHTML = '¿No te llegó? <a href="recuperar.php">Pedir otro código</a>';
        }, 250);
    })();
    </script>
</body>
</html>
