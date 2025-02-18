<?php
session_start();
require_once 'includes/functions.php';
require_once 'src/db.php';

// Asegurarse de que no haya salida antes del JSON
ob_clean(); // Limpiar cualquier salida previa
header('Content-Type: application/json');

// Habilitar registro de errores pero sin mostrarlos
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Log de la petición recibida
$rawInput = file_get_contents('php://input');
error_log('Petición recibida en create_folder.php: ' . $rawInput);

try {
    // Decodificar JSON
    $input = json_decode($rawInput, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Error al decodificar JSON: ' . json_last_error_msg());
    }
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método HTTP no válido');
    }
    
    if (!isset($input['name'])) {
        throw new Exception('Nombre de carpeta no proporcionado');
    }
    
    // Validación y limpieza de datos
    $nombre = trim($input['name']);
    if (empty($nombre)) {
        throw new Exception('El nombre de la carpeta no puede estar vacío');
    }
    
    // Usuario ID
    $usuario_id = isset($input['usuario_id']) ? (int)$input['usuario_id'] : 1;
    
    // Carpeta padre
    $carpeta_padre_id = null;
    if (isset($input['parent_id']) && !empty($input['parent_id'])) {
        $carpeta_padre_id = (int)$input['parent_id'];
    }
    
    // Log de los datos procesados
    error_log(sprintf(
        'Procesando creación de carpeta: nombre=%s, usuario_id=%d, parent_id=%s',
        $nombre,
        $usuario_id,
        $carpeta_padre_id ?? 'NULL'
    ));
    
    // Verificar conexión a la base de datos
    try {
        $testSQL = "SELECT 1";
        $testStmt = $pdo->prepare($testSQL);
        $testStmt->execute();
        error_log('Conexión a la base de datos verificada correctamente');
    } catch (PDOException $e) {
        error_log('ERROR DE CONEXIÓN A LA BD: ' . $e->getMessage());
        throw new Exception('Error de conexión a la base de datos: ' . $e->getMessage());
    }
    
    // Verificar si el usuario existe
    try {
        $userSQL = "SELECT id FROM Usuarios WHERE id = :id";
        $userStmt = $pdo->prepare($userSQL);
        $userStmt->execute([':id' => $usuario_id]);
        if (!$userStmt->fetch()) {
            error_log('Usuario con ID ' . $usuario_id . ' no encontrado');
            throw new Exception('Usuario no encontrado');
        }
        error_log('Usuario verificado correctamente');
    } catch (PDOException $e) {
        error_log('ERROR AL VERIFICAR USUARIO: ' . $e->getMessage());
        throw new Exception('Error al verificar usuario: ' . $e->getMessage());
    }
    
    // Verificar si la carpeta padre existe (si se proporcionó)
    if ($carpeta_padre_id !== null) {
        try {
            $folderSQL = "SELECT id FROM Carpetas WHERE id = :id";
            $folderStmt = $pdo->prepare($folderSQL);
            $folderStmt->execute([':id' => $carpeta_padre_id]);
            if (!$folderStmt->fetch()) {
                error_log('Carpeta padre con ID ' . $carpeta_padre_id . ' no encontrada');
                throw new Exception('Carpeta padre no encontrada');
            }
            error_log('Carpeta padre verificada correctamente');
        } catch (PDOException $e) {
            error_log('ERROR AL VERIFICAR CARPETA PADRE: ' . $e->getMessage());
            throw new Exception('Error al verificar carpeta padre: ' . $e->getMessage());
        }
    }
    
    // Verificar si la función crear_carpeta existe
    try {
        $funcSQL = "SELECT 1 FROM pg_proc WHERE proname = 'crear_carpeta'";
        $funcStmt = $pdo->prepare($funcSQL);
        $funcStmt->execute();
        if (!$funcStmt->fetch()) {
            error_log('Función SQL crear_carpeta no encontrada en la base de datos');
            throw new Exception('Función SQL crear_carpeta no existe');
        }
        error_log('Función crear_carpeta verificada correctamente');
    } catch (PDOException $e) {
        error_log('ERROR AL VERIFICAR FUNCIÓN SQL: ' . $e->getMessage());
        throw new Exception('Error al verificar función SQL: ' . $e->getMessage());
    }
    
    // Crear la carpeta
    $resultado = crearCarpeta($nombre, $usuario_id, $carpeta_padre_id);
    
    // Log del resultado
    error_log('Resultado de creación: ' . json_encode($resultado));
    
    // Asegurarse de que no haya más salida después del JSON
    die(json_encode($resultado));
    
} catch (Exception $e) {
    error_log('Error en create_folder.php: ' . $e->getMessage());
    die(json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]));
}