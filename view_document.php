<?php
session_start();
require_once 'src/db.php';

// Verificar que se recibió un ID de documento
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die('ID de documento no válido');
}

$document_id = intval($_GET['id']);

// Obtener información del documento
try {
    $stmt = $pdo->prepare("
        SELECT d.id, d.titulo, d.ruta_archivo, d.tipo_id, t.nombre as tipo_nombre
        FROM Documentos d
        JOIN Tipos_Documentos t ON d.tipo_id = t.id
        WHERE d.id = :id
    ");
    $stmt->execute([':id' => $document_id]);
    $documento = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$documento) {
        die('Documento no encontrado');
    }

    // Verificar que el archivo existe
    if (!file_exists($documento['ruta_archivo'])) {
        die('El archivo no existe');
    }

    // Determinar el tipo de contenido para la respuesta HTTP
    $extension = pathinfo($documento['ruta_archivo'], PATHINFO_EXTENSION);
    $content_type = '';
    
    switch(strtolower($extension)) {
        case 'pdf':
            $content_type = 'application/pdf';
            break;
        case 'doc':
        case 'docx':
            $content_type = 'application/msword';
            break;
        case 'xls':
        case 'xlsx':
            $content_type = 'application/vnd.ms-excel';
            break;
        case 'jpg':
        case 'jpeg':
            $content_type = 'image/jpeg';
            break;
        case 'png':
            $content_type = 'image/png';
            break;
        case 'txt':
            $content_type = 'text/plain';
            break;
        default:
            $content_type = 'application/octet-stream';
    }
    
    // Establecer los encabezados HTTP apropiados
    header('Content-Type: ' . $content_type);
    header('Content-Disposition: inline; filename="' . basename($documento['ruta_archivo']) . '"');
    
    // Enviar el archivo al navegador
    readfile($documento['ruta_archivo']);
    exit;

} catch (PDOException $e) {
    die('Error al obtener el documento: ' . $e->getMessage());
}