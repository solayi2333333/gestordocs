<?php
// functions.php
require_once 'db.php'; // Incluye la conexión a la base de datos
function listarDocumentos($carpeta_id) {
    global $pdo;

    try {
        $sql = "SELECT d.id, d.titulo, d.descripcion, d.ruta_archivo, 
                       d.fecha_carga, t.nombre as tipo_documento
                FROM Documentos d
                INNER JOIN Documentos_Carpetas dc ON d.id = dc.documento_id
                INNER JOIN Tipos_Documentos t ON d.tipo_id = t.id
                WHERE dc.carpeta_id = :carpeta_id";
                
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':carpeta_id' => $carpeta_id]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        die("Error al listar los documentos: " . $e->getMessage());
    }
}