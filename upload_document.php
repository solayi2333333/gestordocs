<?php
session_start();
require_once 'src/db.php';

header('Content-Type: application/json');

try {
    // Verificar si se recibió un archivo
    if (!isset($_FILES['documento']) || $_FILES['documento']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('No se recibió el archivo correctamente');
    }

    // Obtener datos del formulario
    $titulo = $_POST['titulo'] ?? '';
    $descripcion = $_POST['descripcion'] ?? '';
    $tipo_id = (int)($_POST['tipo_id'] ?? 0);
    $carpeta_id = (int)($_POST['carpeta_id'] ?? 0);
    $usuario_id = (int)($_POST['usuario_id'] ?? 1);

    // Validar datos requeridos
    if (empty($titulo) || $tipo_id === 0 || $carpeta_id === 0) {
        throw new Exception('Faltan datos requeridos');
    }

    // Procesar el archivo
    $archivo = $_FILES['documento'];
    $nombre_archivo = uniqid() . '_' . basename($archivo['name']);
    $directorio_destino = 'uploads/';
    $ruta_completa = $directorio_destino . $nombre_archivo;

    // Crear el directorio si no existe
    if (!file_exists($directorio_destino)) {
        mkdir($directorio_destino, 0777, true);
    }

    // Mover el archivo subido
    if (!move_uploaded_file($archivo['tmp_name'], $ruta_completa)) {
        throw new Exception('Error al mover el archivo subido');
    }

    // Iniciar transacción
    $pdo->beginTransaction();

    // Primero insertar en la tabla Documentos
    $stmt = $pdo->prepare("
        INSERT INTO Documentos (titulo, descripcion, ruta_archivo, tipo_id, usuario_id)
        VALUES (:titulo, :descripcion, :ruta, :tipo_id, :usuario_id)
        RETURNING id
    ");
    
    $stmt->execute([
        ':titulo' => $titulo,
        ':descripcion' => $descripcion,
        ':ruta' => $ruta_completa,
        ':tipo_id' => $tipo_id,
        ':usuario_id' => $usuario_id
    ]);

    $documento_id = $stmt->fetchColumn();

    // Luego insertar en la tabla de relación Documentos_Carpetas
    $stmt = $pdo->prepare("
        INSERT INTO Documentos_Carpetas (documento_id, carpeta_id)
        VALUES (:documento_id, :carpeta_id)
    ");

    $stmt->execute([
        ':documento_id' => $documento_id,
        ':carpeta_id' => $carpeta_id
    ]);

    // Confirmar la transacción
    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Documento subido exitosamente',
        'documento_id' => $documento_id
    ]);

} catch (Exception $e) {
    // Revertir la transacción si hubo error
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    // Si hubo un error, eliminar el archivo si se subió
    if (isset($ruta_completa) && file_exists($ruta_completa)) {
        unlink($ruta_completa);
    }
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}