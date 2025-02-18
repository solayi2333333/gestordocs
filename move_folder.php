<?php
session_start();
require_once 'src/db.php';

// Asegurarse de que no haya salida antes del JSON
ob_clean();
header('Content-Type: application/json');

try {
    // Verificar que la solicitud sea POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }

    // Obtener y decodificar los datos JSON
    $json_data = file_get_contents('php://input');
    $data = json_decode($json_data, true);

    if (!isset($data['folder_id']) || !is_numeric($data['folder_id'])) {
        throw new Exception('ID de carpeta inválido');
    }

    $folder_id = (int)$data['folder_id'];
    $new_parent_id = isset($data['new_parent_id']) && is_numeric($data['new_parent_id']) 
        ? (int)$data['new_parent_id'] 
        : null;
    $usuario_id = 1; // Deberías obtener esto de la sesión del usuario

    // Llamar a la función de PostgreSQL
    $stmt = $pdo->prepare("SELECT mover_carpeta(:folder_id, :new_parent_id, :usuario_id)");
    $stmt->execute([
        ':folder_id' => $folder_id,
        ':new_parent_id' => $new_parent_id,
        ':usuario_id' => $usuario_id
    ]);

    $result = $stmt->fetchColumn();

    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Carpeta movida correctamente']);
    } else {
        throw new Exception('No se pudo mover la carpeta');
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

exit();