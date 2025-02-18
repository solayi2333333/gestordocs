<?php
session_start();
require_once 'src/db.php';

// Asegurarse de que no haya salida antes del JSON
ob_clean(); // Limpiar cualquier salida anterior
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
    $usuario_id = 1; // Deberías obtener esto de la sesión del usuario

    // Llamar a la función de PostgreSQL
    $stmt = $pdo->prepare("SELECT eliminar_carpeta(:folder_id, :usuario_id)");
    $stmt->execute([
        ':folder_id' => $folder_id,
        ':usuario_id' => $usuario_id
    ]);

    $result = $stmt->fetchColumn();

    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Carpeta eliminada correctamente']);
    } else {
        throw new Exception('No se pudo eliminar la carpeta');
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

// Asegurarse de que no haya más salida después del JSON
exit();