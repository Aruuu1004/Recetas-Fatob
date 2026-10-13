<?php
session_start();

// Verificar si el usuario inició sesión. Si no, redirigir al login.
if (!isset($_SESSION['id_usuario'])) {
    header("Location: login.php?error=acceso_denegado");
    exit;
}

require_once "includes/config.php";
require_once "includes/helpers.php";

$id_usuario_actual = $_SESSION['id_usuario'];

// Determinar de qué usuario vamos a ver el perfil (si viene ?id= en la URL, usamos ese; si no, el nuestro)
$id_perfil = isset($_GET['id']) ? (int)$_GET['id'] : $id_usuario_actual;
$es_mi_perfil = ($id_perfil === $id_usuario_actual);

// Obtener la información completa del usuario del perfil
$stmt = $pdo->prepare("SELECT u.*, r.nombre AS rol_nombre
                       FROM USUARIO u
                       JOIN ROLES r ON u.id_rol = r.id_rol
                       WHERE u.id_usuario = :id LIMIT 1");
$stmt->execute([':id' => $id_perfil]);
$usuario = $stmt->fetch();

if (!$usuario) {
    header("Location: perfil.php?error=usuario_no_encontrado");
    exit;
}

// Obtener las restricciones dietarias que tiene asignadas este usuario
$stmtRest = $pdo->prepare("SELECT rd.nombre
                           FROM RESTRICCION_DIETARIA rd
                           JOIN USUARIO_RESTRICCION ur ON rd.id_restriccion = ur.id_restriccion
                           WHERE ur.id_usuario = :id");
$stmtRest->execute([':id' => $id_perfil]);
$restricciones = $stmtRest->fetchAll(PDO::FETCH_COLUMN);

// Para el modo edición: solo aplica si es tu propio perfil
$editando = $es_mi_perfil && isset($_GET['editar']);
$todasRestricciones = [];
$misIds = [];

if ($editando) {
    $todasRestricciones = $pdo->query("SELECT id_restriccion, nombre FROM RESTRICCION_DIETARIA ORDER BY id_restriccion")->fetchAll();
    $stmtMias = $pdo->prepare("SELECT id_restriccion FROM USUARIO_RESTRICCION WHERE id_usuario = :id");
    $stmtMias->execute([':id' => $id_usuario_actual]);
    $misIds = array_map('intval', $stmtMias->fetchAll(PDO::FETCH_COLUMN));
}

$errores = [
    'campos_incompletos' => 'Completá nombre y un correo válido.',
    'contrasena_corta'   => 'La nueva contraseña debe tener al menos 8 caracteres.',
    'email_existente'    => 'Ese correo ya está en uso por otra cuenta.',
    'imagen_invalida'    => 'La imagen no es válida (JPG, PNG, WEBP o GIF, máximo 2 MB).',
    'error_servidor'     => 'Ocurrió un error al guardar. Intentá de nuevo.',
];

// Recetas favoritas del perfil que se está visitando
$stmtFav = $pdo->prepare("SELECT r.id_receta, r.titulo, r.imagen
                          FROM FAVORITO f
                          JOIN RECETA r ON r.id_receta = f.id_receta
                          WHERE f.id_usuario = :id
                          ORDER BY f.fecha DESC");
$stmtFav->execute([':id' => $id_perfil]);
$favoritas = $stmtFav->fetchAll();

// Contar seguidores y seguidos del perfil que se está visitando
$stmtSeguidores = $pdo->prepare("SELECT COUNT(*) FROM seguidores WHERE seguido_id = ?");
$stmtSeguidores->execute([$id_perfil]);
$totalSeguidores = $stmtSeguidores->fetchColumn();

$stmtSiguiendo = $pdo->prepare("SELECT COUNT(*) FROM seguidores WHERE seguidor_id = ?");
$stmtSiguiendo->execute([$id_perfil]);
$totalSiguiendo = $stmtSiguiendo->fetchColumn();

// Verificar si el usuario conectado ya sigue a este perfil
$yaSigue = false;
if (!$es_mi_perfil) {
    $stmtCheck = $pdo->prepare("SELECT id FROM seguidores WHERE seguidor_id = ? AND seguido_id = ?");
    $stmtCheck->execute([$id_usuario_actual, $id_perfil]);
    $yaSigue = $stmtCheck->fetch();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Perfil de <?= htmlspecialchars($usuario['nombre']) ?> - RECETAS FATOB</title>
    <link rel="stylesheet" href="styles/general.css">
    <?php include "views/favicon.php"; ?>
</head>
<body>

    <?php include "views/navbar.php"; ?>

    <main class="container">
        <h2><?= $es_mi_perfil ? "Mi Perfil" : "Perfil de " . htmlspecialchars($usuario['nombre']) ?></h2>

        <?php if (isset($_GET['msg']) && $_GET['msg'] === 'actualizado'): ?>
            <p class="msg ok">Tus datos se actualizaron correctamente.</p>
        <?php endif; ?>
        <?php if (isset($_GET['error']) && isset($errores[$_GET['error']])): ?>
            <p class="msg error"><?= htmlspecialchars($errores[$_GET['error']]) ?></p>
        <?php endif; ?>

        <?php if ($editando): ?>
            <form class="formulario" action="usuarioController.php?accion=editar_perfil" method="POST" enctype="multipart/form-data">
                <label for="nombre">Nombre</label>
                <input type="text" id="nombre" name="nombre" required value="<?= htmlspecialchars($usuario['nombre']) ?>">

                <label for="email">Correo electrónico</label>
                <input type="email" id="email" name="email" required value="<?= htmlspecialchars($usuario['email']) ?>">

                <label for="contrasena_nueva">Nueva contraseña (dejala vacía para no cambiarla)</label>
                <input type="password" id="contrasena_nueva" name="contrasena_nueva" minlength="8" placeholder="Mínimo 8 caracteres">

                <label for="avatar">Foto de perfil</label>
                <?php $avatarActual = avatar_url($usuario['avatar'] ?? ''); ?>
                <?php if ($avatarActual): ?>
                    <img class="mini" src="<?= htmlspecialchars($avatarActual) ?>" alt="Foto actual">
                    <label class="check"><input type="checkbox" name="quitar_avatar" value="1"> Quitar foto actual</label>
                <?php endif; ?>
                <input type="file" id="avatar" name="avatar" accept="image/*">

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
                    
                    <!-- Mostrar email solo si es tu propio perfil (por privacidad opcional) -->
                    <?php if ($es_mi_perfil): ?>
                        <p><strong>Correo electrónico:</strong> <?php echo htmlspecialchars($usuario['email']); ?></p>
                    <?php endif; ?>

                    <p><strong>Rol:</strong> <?php echo htmlspecialchars($usuario['rol_nombre']); ?></p>
                    <p><strong>Fecha de registro:</strong> <?php echo $usuario['fecha_registro']; ?></p>

                    <!-- ESTADÍSTICAS DE SEGUIDORES -->
                    <div class="stats-seguidores" style="margin: 15px 0; padding: 10px; background: #f5f5f5; border-radius: 5px; display: flex; gap: 20px;">
                        <span><strong><?= $totalSeguidores ?></strong> Seguidores</span>
                        <span><strong><?= $totalSiguiendo ?></strong> Siguiendo</span>
                    </div>

                    <!-- BOTÓN DE SEGUIR / DEJAR DE SEGUIR (Solo si visitas el perfil de otro) -->
                    <?php if (!$es_mi_perfil): ?>
                        <form action="usuarioController.php" method="POST" style="margin-bottom: 20px;">
                            <input type="hidden" name="seguido_id" value="<?= $id_perfil ?>">
                            <button type="submit" name="toggle_seguir" class="boton" style="background: <?= $yaSigue ? '#666' : '#ff6600' ?>; color: #fff; border: none; padding: 8px 16px; cursor: pointer; border-radius: 4px;">
                                <?= $yaSigue ? 'Siguiendo' : 'Seguir' ?>
                            </button>
                        </form>
                    <?php endif; ?>

                    <p><strong>Restricciones dietarias:</strong></p>
                    <?php if (!empty($restricciones)): ?>
                        <ul>
                            <?php foreach ($restricciones as $rest): ?>
                                <li><?php echo htmlspecialchars($rest); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p><em>No hay restricciones registradas.</em></p>
                    <?php endif; ?>

                    <hr style="margin: 20px 0;">

                    <div class="acciones">
                        <?php if ($es_mi_perfil): ?>
                            <a class="boton" href="perfil.php?editar=1">Editar perfil</a>
                            <a href="usuarioController.php?accion=logout" style="color: red; font-weight: bold; text-decoration: none; align-self:center;">Cerrar Sesión</a>
                        <?php else: ?>
                            <a class="boton" href="index.php" style="background:#777;">Volver al inicio</a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php $avatarUrl = avatar_url($usuario['avatar'] ?? ''); ?>
                <div class="avatar">
                    <?php if ($avatarUrl): ?>
                        <img src="<?= htmlspecialchars($avatarUrl) ?>" alt="Foto de <?= htmlspecialchars($usuario['nombre']) ?>">
                    <?php else: ?>
                        <!-- Avatar predeterminado: silueta neutra -->
                        <svg viewBox="0 0 100 100" width="70%" role="img" aria-label="Sin foto de perfil">
                            <circle cx="50" cy="35" r="18" fill="#9a9a9a"/>
                            <path d="M14 94c0-21 16-35 36-35s36 14 36 35z" fill="#9a9a9a"/>
                        </svg>
                    <?php endif; ?>
                </div>
            </div>

            <section class="favoritas">
                <h3>Recetas Favoritas</h3>
                <?php if ($favoritas): ?>
                    <div class="carousel fav-carousel">
                        <div class="carousel-track">
                            <?php foreach ($favoritas as $f) include "views/favorita_card.php"; ?>
                        </div>
                        <button type="button" class="carousel-btn carousel-prev" aria-label="Anteriores">&#8249;</button>
                        <button type="button" class="carousel-btn carousel-next" aria-label="Siguientes">&#8250;</button>
                    </div>
                <?php else: ?>
                    <p class="fav-vacio">Todavía no hay recetas favoritas.</p>
                <?php endif; ?>
            </section>
        <?php endif; ?>
    </main>

    <?php include "views/footer.php"; ?>

</body>
</html>