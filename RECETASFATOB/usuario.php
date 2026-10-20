<?php
session_start();
require_once "includes/config.php";
require_once "includes/helpers.php";

// Solo el administrador (rol 1) puede gestionar usuarios
if (!es_admin()) {
    header("Location: login.php?error=acceso_denegado");
    exit;
}

$stmt = $pdo->query("SELECT u.*, r.nombre AS rol_nombre FROM USUARIO u JOIN ROLES r ON u.id_rol = r.id_rol ORDER BY u.id_usuario DESC");
$usuarios = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Gestión de usuarios - RECETAS FATOB</title>
    <link rel="stylesheet" href="styles/general.css?v=5">
    <?php include "views/favicon.php"; ?>
</head>
<body>
    <?php include "views/navbar.php"; ?>

    <main class="container">
        <div class="admin-encabezado">
            <h2>Gestión de usuarios</h2>
            <a class="boton" href="registro.php">+ Agregar usuario</a>
        </div>

        <?php if (($_GET['msg'] ?? '') === 'editado'): ?>
            <p class="msg ok">Usuario actualizado con éxito.</p>
        <?php endif; ?>
        <?php if (($_GET['msg'] ?? '') === 'eliminado'): ?>
            <p class="msg ok">Usuario eliminado correctamente.</p>
        <?php endif; ?>
        <?php if (($_GET['error'] ?? '') === 'autoeliminacion'): ?>
            <p class="msg error">No podés eliminar tu propia cuenta desde acá.</p>
        <?php elseif (isset($_GET['error'])): ?>
            <p class="msg error">Ocurrió un error. Intentá de nuevo.</p>
        <?php endif; ?>

        <div class="tabla-scroll">
            <table class="tabla-admin">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Email</th>
                        <th>Rol</th>
                        <th>Registro</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($usuarios as $u): $id = (int)$u['id_usuario']; ?>
                        <tr>
                            <td data-label="ID">
                                <?= $id ?>
                                <!-- Formulario de edición: los campos de la fila lo enlazan con form="..." -->
                                <form id="editar-<?= $id ?>" action="usuarioController.php?accion=editar" method="POST">
                                    <input type="hidden" name="id_usuario" value="<?= $id ?>">
                                </form>
                            </td>
                            <td data-label="Nombre">
                                <input form="editar-<?= $id ?>" type="text" name="nombre" value="<?= e($u['nombre']) ?>" required>
                            </td>
                            <td data-label="Email">
                                <input form="editar-<?= $id ?>" type="email" name="email" value="<?= e($u['email']) ?>" required>
                            </td>
                            <td data-label="Rol">
                                <select form="editar-<?= $id ?>" name="id_rol">
                                    <option value="1" <?= $u['id_rol'] == 1 ? 'selected' : '' ?>>Administrador</option>
                                    <option value="2" <?= $u['id_rol'] == 2 ? 'selected' : '' ?>>Usuario</option>
                                </select>
                            </td>
                            <td data-label="Registro"><?= e($u['fecha_registro']) ?></td>
                            <td data-label="Acciones" class="acciones">
                                <button form="editar-<?= $id ?>" type="submit" class="btn-editar">Guardar</button>
                                <?php if ($id !== (int)$_SESSION['id_usuario']): ?>
                                    <form action="usuarioController.php?accion=eliminar" method="POST" onsubmit="return confirm('¿Seguro que querés eliminar este usuario?');">
                                        <input type="hidden" name="id_usuario" value="<?= $id ?>">
                                        <button type="submit" class="btn-eliminar">Eliminar</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>

    <?php include "views/footer.php"; ?>
</body>
</html>
