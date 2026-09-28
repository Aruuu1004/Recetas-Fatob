<?php
session_start();
require_once "../includes/config.php";

// Obtener la lista completa de usuarios uniendo con la tabla ROLES
$stmt = $pdo->query("SELECT u.*, r.nombre AS rol_nombre FROM USUARIO u JOIN ROLES r ON u.id_rol = r.id_rol ORDER BY u.id_usuario DESC");
$usuarios = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>CRUD de Usuarios - RECETAS FATOB</title>
    <link rel="stylesheet" href="../styles/ejemplo.css">
</head>
<body>

    <?php include "navbar.php"; ?>

    <main class="container">
        <h2>Gestión de Usuarios (CRUD)</h2>

        <?php if (isset($_GET['msg']) && $_GET['msg'] === 'editado'): ?>
            <p style="color: green;">Usuario actualizado con éxito.</p>
        <?php endif; ?>

        <?php if (isset($_GET['msg']) && $_GET['msg'] === 'eliminado'): ?>
            <p style="color: red;">Usuario eliminado correctamente.</p>
        <?php endif; ?>

        <table border="1" cellpadding="8" cellspacing="0" style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="background-color: #f2f2f2;">
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Email</th>
                    <th>Rol</th>
                    <th>Fecha Registro</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($usuarios as $u): ?>
                    <tr>
                        <form action="../usuarioController.php?accion=editar" method="POST">
                            <td>
                                <?php echo $u['id_usuario']; ?>
                                <input type="hidden" name="id_usuario" value="<?php echo $u['id_usuario']; ?>">
                            </td>
                            <td>
                                <input type="text" name="nombre" value="<?php echo htmlspecialchars($u['nombre']); ?>" required>
                            </td>
                            <td>
                                <input type="email" name="email" value="<?php echo htmlspecialchars($u['email']); ?>" required>
                            </td>
                            <td>
                                <select name="id_rol">
                                    <option value="1" <?php echo ($u['id_rol'] == 1) ? 'selected' : ''; ?>>Administrador</option>
                                    <option value="2" <?php echo ($u['id_rol'] == 2) ? 'selected' : ''; ?>>Usuario</option>
                                </select>
                            </td>
                            <td><?php echo $u['fecha_registro']; ?></td>
                            <td>
                                <button type="submit">Guardar</button>
                                <a href="../usuarioController.php?accion=eliminar&id=<?php echo $u['id_usuario']; ?>"
                                   onclick="return confirm('¿Seguro que querés eliminar este usuario?');"
                                   style="color: red; margin-left: 10px;">Eliminar</a>
                            </td>
                        </form>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </main>

    <?php include "footer.php"; ?>

</body>
</html>