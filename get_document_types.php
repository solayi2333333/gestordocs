<?php
// Activar la visualización de errores para depuración
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

try {
    require_once 'src/db.php';

    // Verificar la conexión PDO
    if (!isset($pdo)) {
        throw new Exception('Error de conexión a la base de datos');
    }

    // Ejecutar la consulta con try/catch específico
    try {
        // Cambiar a marcadores de posición PDO estándar
        $stmt = $pdo->prepare("SELECT id, nombre FROM Tipos_Documentos ORDER BY nombre");
        $stmt->execute();
        
        // Verificar si la consulta fue exitosa
        if ($stmt === false) {
            throw new PDOException('Error al ejecutar la consulta');
        }

        $tipos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Validar el resultado
        if ($tipos === false) {
            throw new PDOException('Error al obtener los resultados');
        }

        // Enviar respuesta JSON
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'data' => $tipos ?: [], // Asegurar que siempre devolvemos un array
            'message' => empty($tipos) ? 'No hay tipos de documentos disponibles' : null
        ]);

    } catch (PDOException $e) {
        // Log del error específico de la base de datos
        error_log('Error en la base de datos: ' . $e->getMessage());
        throw new Exception('Error en la consulta de la base de datos');
    }

} catch (Exception $e) {
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}