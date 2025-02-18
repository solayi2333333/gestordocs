<?php
// Configuración de la base de datos
$host = 'localhost'; // o la dirección IP del servidor de la base de datos
$dbname = 'gestion_documental'; // nombre de la base de datos
$user = 'postgres'; // usuario de la base de datos
$password = 'PgSena2024'; // contraseña del usuario

try {
    // Crear una nueva instancia de PDO para la conexión
    $dsn = "pgsql:host=$host;dbname=$dbname";
    $pdo = new PDO($dsn, $user, $password);

    // Configurar PDO para que lance excepciones en caso de errores
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Opcional: Configurar el charset (si es necesario)
    $pdo->exec("SET NAMES 'utf8'");

} catch (PDOException $e) {
    // En caso de error, mostrar el mensaje de error
    die("Error de conexión a la base de datos: " . $e->getMessage());
}
?>