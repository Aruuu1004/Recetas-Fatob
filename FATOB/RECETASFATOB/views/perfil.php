<?php
session_start();

// Verificar si el usuario inició sesión. Si no, redirigir al login.
if (!isset($_SESSION['id_usuario'])) {
    header("Location: login.php?error=acceso_denegado");
    exit;
}

require_once "../includes/config.php";

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
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mi Perfil - RECETAS FATOB</title>
    <link rel="stylesheet" href="../styles/ejemplo.css">
</head>
<body>

    <?php include "navbar.php"; ?>

    <main class="container">
        <h2>Mi Perfil</h2>

        <div class="card-perfil" style="border: 1px solid #ccc; padding: 20px; max-width: 400px; border-radius: 8px;">
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

            <a href="../usuarioController.php?accion=logout" style="color: red; font-weight: bold; text-decoration: none;">Cerrar Sesión</a>
        </div>
    </main>

    <?php include "footer.php"; ?>

</body>
</html>