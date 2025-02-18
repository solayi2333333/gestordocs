<?php
// functions.php
require_once 'db.php'; // Incluye la conexión a la base de datos
function listarSubcarpetas($carpeta_padre_id) {
    global $pdo;

    try {
        // Consulta SQL para obtener las subcarpetas de una carpeta específica
        $sql = "SELECT 
                    c.id, 
                    c.nombre, 
                    c.carpeta_padre_id
                FROM Carpetas c
                WHERE c.carpeta_padre_id = :carpeta_padre_id
                ORDER BY c.nombre ASC";
                
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':carpeta_padre_id' => $carpeta_padre_id]);

        $subcarpetas = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Si no hay subcarpetas, devolver un array vacío en lugar de false
        return $subcarpetas ?: [];

    } catch (PDOException $e) {
        // Log del error para debugging
        error_log("Error al listar subcarpetas: " . $e->getMessage());
        
        // Devolver array vacío en caso de error
        return [];
    }
}

// Función auxiliar para verificar si una carpeta tiene subcarpetas
function tieneSubcarpetas($carpeta_id) {
    global $pdo;

    try {
        $sql = "SELECT COUNT(*) 
                FROM Carpetas 
                WHERE carpeta_padre_id = :carpeta_id";
                
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':carpeta_id' => $carpeta_id]);

        return (int)$stmt->fetchColumn() > 0;

    } catch (PDOException $e) {
        error_log("Error al verificar subcarpetas: " . $e->getMessage());
        return false;
    }
}
?>