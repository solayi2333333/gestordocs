<?php
session_start();
require_once 'src/db.php';

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('HTTP/1.0 400 Bad Request');
    echo json_encode(['success' => false, 'message' => 'ID de documento no proporcionado']);
    exit;
}

$document_id = (int)$_GET['id'];

try {
    // 1. Obtener información del documento
    $sql = "SELECT d.titulo, d.ruta_archivo, td.nombre as tipo 
    FROM Documentos d 
    JOIN Tipos_Documentos td ON d.tipo_id = td.id 
    WHERE d.id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $document_id]);
    $documento = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$documento) {
        header('HTTP/1.0 404 Not Found');
        echo json_encode(['success' => false, 'message' => 'Documento no encontrado']);
        exit;
    }

    // 2. Verificar que el archivo exista
    $ruta_completa = $documento['ruta_archivo']; // Aquí está el cambio
    if (!file_exists($ruta_completa)) {
        header('HTTP/1.0 404 Not Found');
        echo json_encode([
            'success' => false, 
            'message' => 'El archivo físico no existe', 
            'path' => $ruta_completa,
            'document_info' => $documento
        ]);
        exit;
    }

    // 3. Configurar las cabeceras para la descarga
    $filename = pathinfo($documento['ruta_archivo'], PATHINFO_BASENAME);
    $extension = pathinfo($documento['ruta_archivo'], PATHINFO_EXTENSION);
    
    // Determinar el tipo MIME
    $content_type = 'application/octet-stream'; // Por defecto
    switch (strtolower($extension)) {
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
    }

    // 4. Iniciar la descarga
    header('Content-Description: File Transfer');
    header('Content-Type: ' . $content_type);
    header('Content-Disposition: attachment; filename="' . $documento['titulo'] . '.' . $extension . '"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($ruta_completa));
    
    // 5. Leer y enviar el archivo
    readfile($ruta_completa);
    exit;

} catch (PDOException $e) {
    header('HTTP/1.0 500 Internal Server Error');
    echo json_encode(['success' => false, 'message' => 'Error al obtener el documento: ' . $e->getMessage()]);
    exit;
} catch (Exception $e) {
    header('HTTP/1.0 500 Internal Server Error');
    echo json_encode(['success' => false, 'message' => 'Error inesperado: ' . $e->getMessage()]);
    exit;
}
?>