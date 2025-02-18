<?php
session_start();
require_once 'src/db.php';

// Asegurarse de que la petición sea POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

// Obtener y decodificar los datos JSON del cuerpo de la petición
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['name']) || empty(trim($data['name']))) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'El nombre de la carpeta es requerido']);
    exit;
}

try {
    // Preparar los parámetros para la función crear_carpeta
    $nombre = trim($data['name']);
    $carpeta_padre_id = isset($data['parent_id']) ? (int)$data['parent_id'] : null;
    $usuario_id = isset($_SESSION['usuario_id']) ? $_SESSION['usuario_id'] : 2; // Usuario por defecto si no hay sesión

    // Llamar a la función crear_carpeta
    $sql = "SELECT crear_carpeta($1, $2, $3) as nueva_carpeta_id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$nombre, $carpeta_padre_id, $usuario_id]);
    
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $nueva_carpeta_id = $result['nueva_carpeta_id'];

    // Obtener los detalles de la carpeta recién creada
    $sql_detalles = "SELECT id, nombre, carpeta_padre_id, fecha_creacion 
                     FROM Carpetas 
                     WHERE id = :id";
    $stmt_detalles = $pdo->prepare($sql_detalles);
    $stmt_detalles->execute([':id' => $nueva_carpeta_id]);
    $carpeta = $stmt_detalles->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'folder_id' => $nueva_carpeta_id,
        'folder' => [
            'id' => $carpeta['id'],
            'nombre' => $carpeta['nombre'],
            'carpeta_padre_id' => $carpeta['carpeta_padre_id'],
            'fecha_creacion' => $carpeta['fecha_creacion']
        ]
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error al crear la carpeta',
        'error' => $e->getMessage()
    ]);
}
?>