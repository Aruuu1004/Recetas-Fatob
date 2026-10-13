<?php
// includes/config.php
$host = "localhost";
$db_name = "recetas_fatob";
$username = "root";
$password = "";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db_name;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error de conexión a la base de datos: " . $e->getMessage());
}

// Crea la tabla de fotos de las recetas si todavía no existe (así no hace falta correr el SQL a mano)
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS RECETA_IMAGEN (
        id_imagen INT AUTO_INCREMENT PRIMARY KEY,
        id_receta INT NOT NULL,
        archivo VARCHAR(255) NOT NULL,
        orden INT NOT NULL DEFAULT 0,
        FOREIGN KEY (id_receta) REFERENCES RECETA(id_receta) ON DELETE CASCADE
    )");
} catch (PDOException $e) {
    // Si no se pudo crear, el resto de la página sigue funcionando
}
?>