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
    header("Location: perfil.php");
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
$seccion = $_GET['seccion'] ?? '';
if (isset($_GET['editar'])) $seccion = 'editar';
if (!$es_mi_perfil || !in_array($seccion, ['editar', 'recetas', 'comentarios', 'favoritas'], true)) $seccion = '';
$editando = $es_mi_perfil && $seccion === 'editar';
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
$stmtFav = $pdo->prepare("SELECT r.id_receta, r.titulo, r.imagen, r.tiempo_preparacion,
                                 COALESCE(AVG(c.puntuacion), 0) AS promedio, COUNT(c.id_calificacion) AS votos
                          FROM FAVORITO f
                          JOIN RECETA r ON r.id_receta = f.id_receta
                          LEFT JOIN CALIFICACION c ON c.id_receta = r.id_receta
                          WHERE f.id_usuario = :id
                          GROUP BY r.id_receta, f.fecha
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

// Cantidad de recetas publicadas
$stmtNRec = $pdo->prepare("SELECT COUNT(*) FROM RECETA WHERE id_usuario = ?");
$stmtNRec->execute([$id_perfil]);
$totalRecetas = (int)$stmtNRec->fetchColumn();
$totalFavoritas = count($favoritas);

// Verificar si el usuario conectado ya sigue a este perfil
$yaSigue = false;
if (!$es_mi_perfil) {
    $stmtCheck = $pdo->prepare("SELECT id FROM seguidores WHERE seguidor_id = ? AND seguido_id = ?");
    $stmtCheck->execute([$id_usuario_actual, $id_perfil]);
    $yaSigue = $stmtCheck->fetch();
}

// Solo en mi propio perfil: mis recetas y mis comentarios
$misRecetas = [];
$misComentarios = [];
if ($es_mi_perfil && $seccion === 'recetas') {
    $st = $pdo->prepare("SELECT id_receta, titulo, tiempo_preparacion, fecha_creacion FROM RECETA WHERE id_usuario = :id ORDER BY fecha_creacion DESC");
    $st->execute([':id' => $id_perfil]);
    $misRecetas = $st->fetchAll();
}
if ($es_mi_perfil && $seccion === 'comentarios') {
    $st = $pdo->prepare("SELECT c.id_comentario, c.texto, c.fecha, c.id_receta, r.titulo
                         FROM COMENTARIO c JOIN RECETA r ON r.id_receta = c.id_receta
                         WHERE c.id_usuario = :id ORDER BY c.fecha DESC");
    $st->execute([':id' => $id_perfil]);
    $misComentarios = $st->fetchAll();
}

$avatarHtml = avatar_interior($usuario['avatar'] ?? '', $usuario['nombre']);
$titulos = ['editar' => 'Editar perfil', 'recetas' => 'Mis recetas', 'comentarios' => 'Mis comentarios', 'favoritas' => 'Mis recetas favoritas'];
$icoLapiz = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 20l4-1 11-11-3-3L5 16z"/></svg>';
$icoLibro = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 4h11a3 3 0 0 1 3 3v13H8a3 3 0 0 1-3-3zM5 17a3 3 0 0 1 3-3h11"/></svg>';
$icoChat = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 5h16v11H9l-5 4z"/></svg>';
$icoCorazon = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 20s-8-5-8-11a4.5 4.5 0 0 1 8-2.8A4.5 4.5 0 0 1 20 9c0 6-8 11-8 11z"/></svg>';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Perfil de <?= htmlspecialchars($usuario['nombre']) ?> - RECETAS FATOB</title>
    <link rel="stylesheet" href="styles/general.css?v=5">
    <?php include "views/favicon.php"; ?>
</head>
<body>

    <?php include "views/navbar.php"; ?>

    <main class="container container-ancho">
        <?php if (isset($_GET['msg']) && $_GET['msg'] === 'actualizado'): ?>
            <p class="msg ok">Tus datos se actualizaron correctamente.</p>
        <?php endif; ?>
        <?php if (isset($_GET['error']) && isset($errores[$_GET['error']])): ?>
            <p class="msg error"><?= htmlspecialchars($errores[$_GET['error']]) ?></p>
        <?php endif; ?>

        <?php if ($seccion !== ''): /* ===== Mi perfil con menú lateral ===== */ ?>
        <div class="perfil-layout">
            <aside class="perfil-lateral">
                <a href="perfil.php" style="color:inherit" aria-label="Volver a mi perfil">
                    <div class="perfil-avatar"><?= $avatarHtml ?></div>
                    <h1 class="perfil-nombre"><?= htmlspecialchars($usuario['nombre']) ?></h1>
                </a>
                <p class="perfil-email"><?= htmlspecialchars($usuario['email']) ?></p>
                <ul class="menu-perfil">
                    <li><a href="perfil.php?seccion=editar" class="<?= $seccion === 'editar' ? 'activo' : '' ?>"><?= $icoLapiz ?> Editar perfil</a></li>
                    <li><a href="perfil.php?seccion=recetas" class="<?= $seccion === 'recetas' ? 'activo' : '' ?>"><?= $icoLibro ?> Mis recetas</a></li>
                    <li><a href="perfil.php?seccion=comentarios" class="<?= $seccion === 'comentarios' ? 'activo' : '' ?>"><?= $icoChat ?> Mis comentarios</a></li>
                    <li><a href="perfil.php?seccion=favoritas" class="<?= $seccion === 'favoritas' ? 'activo' : '' ?>"><?= $icoCorazon ?> Mis recetas favoritas</a></li>
                </ul>
            </aside>

            <section class="perfil-principal">
                <h2><?= $titulos[$seccion] ?></h2>

                <?php if ($seccion === 'editar'): ?>
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
                        <div class="chips">
                            <?php foreach ($todasRestricciones as $rd): ?>
                                <label class="chip">
                                    <input type="checkbox" name="restricciones[]" value="<?= (int)$rd['id_restriccion'] ?>"
                                        <?= in_array((int)$rd['id_restriccion'], $misIds, true) ? 'checked' : '' ?>>
                                    <?= htmlspecialchars($rd['nombre']) ?>
                                </label>
                            <?php endforeach; ?>
                        </div>

                        <div class="acciones">
                            <button type="submit">Guardar cambios</button>
                            <a class="boton boton-claro" href="perfil.php">Cancelar</a>
                        </div>
                    </form>

                <?php elseif ($seccion === 'recetas'): ?>
                    <?php if (!$misRecetas): ?>
                        <p class="fav-vacio">Todavía no publicaste recetas.</p>
                    <?php else: ?>
                        <ul class="lista-simple">
                            <?php foreach ($misRecetas as $mr): ?>
                                <li>
                                    <span class="t"><a href="receta_detalle.php?id=<?= (int)$mr['id_receta'] ?>"><?= e($mr['titulo']) ?></a></span>
                                    <span class="acc">
                                        <a class="btn-ver" href="receta_detalle.php?id=<?= (int)$mr['id_receta'] ?>">Ver</a>
                                        <a class="btn-editar" href="receta_form.php?id=<?= (int)$mr['id_receta'] ?>">Editar</a>
                                    </span>
                                    <p class="s"><?= (int)$mr['tiempo_preparacion'] ?> min · publicada el <?= e(date('d/m/Y', strtotime($mr['fecha_creacion']))) ?></p>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                    <div class="acciones"><a class="boton" href="receta_form.php">Publicar una receta</a></div>

                <?php elseif ($seccion === 'comentarios'): ?>
                    <?php if (!$misComentarios): ?>
                        <p class="fav-vacio">Todavía no hiciste comentarios.</p>
                    <?php else: ?>
                        <ul class="lista-simple">
                            <?php foreach ($misComentarios as $mc): ?>
                                <li>
                                    <span class="t"><a href="receta_detalle.php?id=<?= (int)$mc['id_receta'] ?>#comentarios"><?= e($mc['titulo']) ?></a></span>
                                    <span class="acc">
                                        <form action="comentarioController.php?accion=eliminar" method="POST" onsubmit="return confirm('¿Eliminar comentario?');">
                                            <input type="hidden" name="id_comentario" value="<?= (int)$mc['id_comentario'] ?>">
                                            <input type="hidden" name="id_receta" value="<?= (int)$mc['id_receta'] ?>">
                                            <button type="submit" class="peligro chico">Eliminar</button>
                                        </form>
                                    </span>
                                    <p class="s"><?= e(date('d/m/Y', strtotime($mc['fecha']))) ?> — <?= e($mc['texto']) ?></p>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>

                <?php else: /* favoritas */ ?>
                    <?php if ($favoritas): ?>
                        <div class="grid grid-4">
                            <?php foreach ($favoritas as $f) include "views/favorita_card.php"; ?>
                        </div>
                    <?php else: ?>
                        <p class="fav-vacio">Todavía no hay recetas favoritas. Tocá el ♡ en cualquier receta para guardarla acá.</p>
                    <?php endif; ?>
                <?php endif; ?>
            </section>
        </div>

        <?php else: /* ===== Resumen del perfil (propio o de otra persona) ===== */ ?>
        <section class="perfil-solo">
            <div class="perfil-card">
                <div class="perfil-avatar"><?= $avatarHtml ?></div>
                <h1 class="perfil-nombre"><?= htmlspecialchars($usuario['nombre']) ?></h1>
                <?php if ($es_mi_perfil): ?>
                    <p class="perfil-email"><?= htmlspecialchars($usuario['email']) ?></p>
                <?php endif; ?>
                <span class="perfil-rol"><?= htmlspecialchars($usuario['rol_nombre']) ?></span>

                <div class="perfil-stats">
                    <div><strong><?= (int)$totalSeguidores ?></strong><span>Seguidores</span></div>
                    <div><strong><?= (int)$totalSiguiendo ?></strong><span>Seguidos</span></div>
                    <?php if ($es_mi_perfil): ?>
                        <a href="perfil.php?seccion=recetas"><strong><?= $totalRecetas ?></strong><span>Recetas</span></a>
                        <a href="perfil.php?seccion=favoritas"><strong><?= $totalFavoritas ?></strong><span>Favoritas</span></a>
                    <?php else: ?>
                        <div><strong><?= $totalRecetas ?></strong><span>Recetas</span></div>
                        <div><strong><?= $totalFavoritas ?></strong><span>Favoritas</span></div>
                    <?php endif; ?>
                </div>

                <div class="perfil-datos">
                    <p style="margin:0">Miembro desde el <?= e(date('d/m/Y', strtotime($usuario['fecha_registro']))) ?></p>
                    <?php if (!empty($restricciones)): ?>
                        <div class="chips">
                            <?php foreach ($restricciones as $rest): ?><span class="chip"><?= htmlspecialchars($rest) ?></span><?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="perfil-acciones">
                    <?php if ($es_mi_perfil): ?>
                        <a class="boton" href="perfil.php?seccion=editar">Editar perfil</a>
                        <a class="boton boton-claro" href="perfil.php?seccion=favoritas">Mis recetas favoritas</a>
                        <a class="boton boton-claro" href="usuarioController.php?accion=logout">Cerrar sesión</a>
                    <?php else: ?>
                        <form action="usuarioController.php" method="POST">
                            <input type="hidden" name="seguido_id" value="<?= $id_perfil ?>">
                            <button type="submit" name="toggle_seguir" class="<?= $yaSigue ? 'boton-claro' : '' ?>"><?= $yaSigue ? 'Siguiendo' : 'Seguir' ?></button>
                        </form>
                        <a class="boton boton-claro" href="index.php">Volver al inicio</a>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (!$es_mi_perfil): ?>
                <section class="perfil-fav">
                    <h2>Recetas favoritas</h2>
                    <?php if ($favoritas): ?>
                        <div class="grid">
                            <?php foreach ($favoritas as $f) include "views/favorita_card.php"; ?>
                        </div>
                    <?php else: ?>
                        <p class="fav-vacio">Todavía no hay recetas favoritas.</p>
                    <?php endif; ?>
                </section>
            <?php endif; ?>
        </section>
        <?php endif; ?>
    </main>

    <?php include "views/footer.php"; ?>

</body>
</html>
