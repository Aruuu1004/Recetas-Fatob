<?php
session_start();
require_once "includes/config.php";
require_once "includes/helpers.php";
requiere_login();

$receta = null;
$id = (int)($_GET['id'] ?? 0);
if ($id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM RECETA WHERE id_receta = :id");
    $stmt->execute([':id' => $id]);
    $receta = $stmt->fetch();
    if (!$receta || !puede_editar($receta['id_usuario'])) {
        header("Location: recetas.php");
        exit;
    }
}
$editando = $receta !== null;
$fotos = $editando ? imagenes_receta($pdo, $receta, true) : [];
$errores = [
    'campos_incompletos' => 'Completá todos los campos obligatorios.',
    'imagen_invalida'    => 'Alguna imagen no es válida (JPG, PNG, WEBP o GIF, máximo 2 MB cada una).',
    'demasiadas_imagenes' => 'Podés tener hasta ' . MAX_FOTOS_RECETA . ' fotos por receta.',
    'error_servidor'     => 'Ocurrió un error al guardar. Intentá de nuevo.',
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $editando ? 'Editar' : 'Nueva' ?> receta - RECETAS FATOB</title>
    <link rel="stylesheet" href="styles/general.css?v=5">
    <?php include "views/favicon.php"; ?>
</head>
<body>
    <?php include "views/navbar.php"; ?>

    <main class="container">
        <h2><?= $editando ? 'Editar receta' : 'Nueva receta' ?></h2>

        <?php if (isset($_GET['error']) && isset($errores[$_GET['error']])): ?>
            <p class="msg error"><?= e($errores[$_GET['error']]) ?></p>
        <?php endif; ?>

        <form class="formulario" action="recetasController.php?accion=<?= $editando ? 'editar' : 'crear' ?>" method="POST" enctype="multipart/form-data">
            <?php if ($editando): ?>
                <input type="hidden" name="id_receta" value="<?= (int)$receta['id_receta'] ?>">
            <?php endif; ?>

            <label for="titulo">Título</label>
            <input type="text" id="titulo" name="titulo" maxlength="150" required value="<?= e($receta['titulo'] ?? '') ?>">

            <label for="descripcion">Descripción breve</label>
            <textarea id="descripcion" name="descripcion" maxlength="500" rows="3" required><?= e($receta['descripcion'] ?? '') ?></textarea>

            <label for="ingredientes">Ingredientes (uno por línea)</label>
            <textarea id="ingredientes" name="ingredientes" rows="6" required><?= e($receta['ingredientes'] ?? '') ?></textarea>

            <label for="instrucciones">Preparación</label>
            <textarea id="instrucciones" name="instrucciones" rows="8" required><?= e($receta['instrucciones'] ?? '') ?></textarea>

            <label for="tiempo_preparacion">Tiempo de preparación (minutos)</label>
            <input type="number" id="tiempo_preparacion" name="tiempo_preparacion" min="1" required value="<?= e($receta['tiempo_preparacion'] ?? '') ?>">

            <label for="imagenes">Fotos (opcional, hasta <?= MAX_FOTOS_RECETA ?>)</label>
            <?php if ($fotos): ?>
                <div class="fotos-actuales">
                    <?php foreach ($fotos as $ft): ?>
                        <div class="foto-actual">
                            <img class="mini" src="img/recetas/<?= e($ft['archivo']) ?>" alt="Foto actual">
                            <label class="check"><input type="checkbox" name="quitar[]" value="<?= (int)$ft['id'] ?>"> Quitar</label>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <input type="file" id="imagenes" name="imagenes[]" accept="image/*" multiple>
            <small class="ayuda">Podés elegir varias a la vez. JPG, PNG, WEBP o GIF, máximo 2 MB cada una.</small>

            <button type="submit"><?= $editando ? 'Guardar cambios' : 'Publicar receta' ?></button>
        </form>
    </main>

    <?php include "views/footer.php"; ?>

    <script>
    // Avisa antes de enviar si hay demasiadas fotos o alguna pesa más de 2 MB
    (function () {
        var form = document.querySelector('form.formulario');
        var input = document.getElementById('imagenes');
        var max = <?= MAX_FOTOS_RECETA ?>;
        var actuales = <?= count($fotos) ?>;
        form.addEventListener('submit', function (ev) {
            var quitadas = form.querySelectorAll('input[name="quitar[]"]:checked').length;
            var nuevas = input.files.length;
            if (actuales - quitadas + nuevas > max) {
                alert('Podés tener hasta ' + max + ' fotos por receta.');
                ev.preventDefault();
                return;
            }
            for (var i = 0; i < nuevas; i++) {
                if (input.files[i].size > 2 * 1024 * 1024) {
                    alert('La foto "' + input.files[i].name + '" pesa más de 2 MB.');
                    ev.preventDefault();
                    return;
                }
            }
        });
    })();
    </script>
</body>
</html>
