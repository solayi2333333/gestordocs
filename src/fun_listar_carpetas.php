<?php
// functions.php
require_once 'db.php'; // Incluye la conexión a la base de datos

function listarCarpetas($usuario_id) {
    global $pdo;

    try {
        // Consulta SQL para obtener solo las carpetas raíz del usuario
        $sql = "SELECT id, nombre, carpeta_padre_id 
                FROM Carpetas 
                WHERE usuario_id = :usuario_id 
                AND carpeta_padre_id IS NULL";  // Solo carpetas que no tienen padre
                
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':usuario_id' => $usuario_id]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        die("Error al listar las carpetas: " . $e->getMessage());
    }
}
?>