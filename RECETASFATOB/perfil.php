<?php
session_start();

// Verificar si el usuario inició sesión. Si no, redirigir al login.
if (!isset($_SESSION['id_usuario'])) {
    header("Location: login.php?error=acceso_denegado");
    exit;
}

require_once "includes/config.php";

// Obtener la información completa del usuario autenticado
$id_usuario = $_SESSION['id_usuario'];
$stmt = $pdo->prepare("SELECT u.*, r.nombre AS rol_nombre
                       FROM USUARIO u
                       JOIN ROLES r ON u.id_rol = r.id_rol
                       WHERE u.id_usuario = :id LIMIT 1");
$stmt->execute([':id' => $id_usuario]);
$usuario = $stmt->fetch();

// Obtener las restricciones dietarias que tiene asignadas
$stmtRest = $pdo->prepare("SELECT rd.nombre
                           FROM RESTRICCION_DIETARIA rd
                           JOIN USUARIO_RESTRICCION ur ON rd.id_restriccion = ur.id_restriccion
                           WHERE ur.id_usuario = :id");
$stmtRest->execute([':id' => $id_usuario]);
$restricciones = $stmtRest->fetchAll(PDO::FETCH_COLUMN);

// Para el modo edición: todas las restricciones y cuáles tiene marcadas el usuario
$editando = isset($_GET['editar']);
$todasRestricciones = $pdo->query("SELECT id_restriccion, nombre FROM RESTRICCION_DIETARIA ORDER BY id_restriccion")->fetchAll();
$stmtMias = $pdo->prepare("SELECT id_restriccion FROM USUARIO_RESTRICCION WHERE id_usuario = :id");
$stmtMias->execute([':id' => $id_usuario]);
$misIds = array_map('intval', $stmtMias->fetchAll(PDO::FETCH_COLUMN));

$errores = [
    'campos_incompletos' => 'Completá nombre y un correo válido.',
    'contrasena_corta'   => 'La nueva contraseña debe tener al menos 8 caracteres.',
    'email_existente'    => 'Ese correo ya está en uso por otra cuenta.',
    'error_servidor'     => 'Ocurrió un error al guardar. Intentá de nuevo.',
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mi Perfil - RECETAS FATOB</title>
    <link rel="stylesheet" href="styles/general.css">

</head>
<body>

    <?php include "views/navbar.php"; ?>

    <main class="container">
        <h2>Mi Perfil</h2>

        <?php if (isset($_GET['msg']) && $_GET['msg'] === 'actualizado'): ?>
            <p class="msg ok">Tus datos se actualizaron correctamente.</p>
        <?php endif; ?>
        <?php if (isset($_GET['error']) && isset($errores[$_GET['error']])): ?>
            <p class="msg error"><?= htmlspecialchars($errores[$_GET['error']]) ?></p>
        <?php endif; ?>

        <?php if ($editando): ?>
            <form class="formulario" action="usuarioController.php?accion=editar_perfil" method="POST">
                <label for="nombre">Nombre</label>
                <input type="text" id="nombre" name="nombre" required value="<?= htmlspecialchars($usuario['nombre']) ?>">

                <label for="email">Correo electrónico</label>
                <input type="email" id="email" name="email" required value="<?= htmlspecialchars($usuario['email']) ?>">

                <label for="contrasena_nueva">Nueva contraseña (dejala vacía para no cambiarla)</label>
                <input type="password" id="contrasena_nueva" name="contrasena_nueva" minlength="8" placeholder="Mínimo 8 caracteres">

                <label>Restricciones dietarias</label>
                <?php foreach ($todasRestricciones as $rd): ?>
                    <label class="check">
                        <input type="checkbox" name="restricciones[]" value="<?= (int)$rd['id_restriccion'] ?>"
                            <?= in_array((int)$rd['id_restriccion'], $misIds, true) ? 'checked' : '' ?>>
                        <?= htmlspecialchars($rd['nombre']) ?>
                    </label>
                <?php endforeach; ?>

                <div class="acciones">
                    <button type="submit">Guardar cambios</button>
                    <a class="boton" href="perfil.php" style="background:#777;">Cancelar</a>
                </div>
            </form>
        <?php else: ?>
            <div class="perfil-grid">
            <div class="card-perfil">
                <p><strong>Nombre:</strong> <?php echo htmlspecialchars($usuario['nombre']); ?></p>
                <p><strong>Correo electrónico:</strong> <?php echo htmlspecialchars($usuario['email']); ?></p>
                <p><strong>Rol:</strong> <?php echo htmlspecialchars($usuario['rol_nombre']); ?></p>
                <p><strong>Fecha de registro:</strong> <?php echo $usuario['fecha_registro']; ?></p>

                <p><strong>Restricciones dietarias:</strong></p>
                <?php if (!empty($restricciones)): ?>
                    <ul>
                        <?php foreach ($restricciones as $rest): ?>
                            <li><?php echo htmlspecialchars($rest); ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p><em>No tenés restricciones registradas.</em></p>
                <?php endif; ?>

                <hr style="margin: 20px 0;">

                <div class="acciones">
                    <a class="boton" href="perfil.php?editar=1">Editar perfil</a>
                    <a href="usuarioController.php?accion=logout" style="color: red; font-weight: bold; text-decoration: none; align-self:center;">Cerrar Sesión</a>
                </div>
            </div>
            <div class="avatar">👤</div>
            </div>
        <?php endif; ?>
    </main>

    <?php include "views/footer.php"; ?>

</body>
</html>