<?php
require_once 'src/db.php';

// Función para listar carpetas principales
function listarCarpetas($usuario_id) {
    global $pdo;

    try {
        // Consulta SQL para obtener solo las carpetas raíz del usuario
        $sql = "SELECT id, nombre, carpeta_padre_id 
                FROM Carpetas 
                WHERE usuario_id = :usuario_id 
                AND carpeta_padre_id IS NULL";
                
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':usuario_id' => $usuario_id]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error al listar las carpetas: " . $e->getMessage());
        return [];
    }
}

// Función para listar subcarpetas
function listarSubcarpetas($carpeta_padre_id) {
    global $pdo;

    try {
        $sql = "SELECT 
                    c.id, 
                    c.nombre, 
                    c.carpeta_padre_id
                FROM Carpetas c
                WHERE c.carpeta_padre_id = :carpeta_padre_id
                ORDER BY c.nombre ASC";
                
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':carpeta_padre_id' => $carpeta_padre_id]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error al listar subcarpetas: " . $e->getMessage());
        return [];
    }
}

// Función para verificar si una carpeta tiene subcarpetas
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

// Función auxiliar para formatear la fecha
function timeAgo($datetime) {
    $now = new DateTime();
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);
    
    if ($diff->d == 0) {
        if ($diff->h == 0) {
            return "hace " . $diff->i . " minutos";
        }
        return "hace " . $diff->h . " horas";
    }
    if ($diff->d == 1) {
        return "hace 1 día";
    }
    return "hace " . $diff->d . " días";
}

// Función para obtener detalles de una carpeta
function obtenerCarpeta($carpeta_id) {
    global $pdo;

    try {
        $sql = "SELECT id, nombre, carpeta_padre_id 
                FROM Carpetas 
                WHERE id = :id";
                
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $carpeta_id]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error al obtener detalles de la carpeta: " . $e->getMessage());
        return null;
    }
}

// Función para listar documentos
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
        error_log("Error al listar los documentos: " . $e->getMessage());
        return [];
    }
}

function crearCarpeta($nombre, $usuario_id, $carpeta_padre_id = null) {
    global $pdo;
    
    try {
        // Validaciones básicas
        if (empty(trim($nombre))) {
            return [
                'success' => false,
                'message' => 'El nombre de la carpeta no puede estar vacío',
                'folder_id' => null
            ];
        }

        // Verificar que el usuario existe
        $stmt = $pdo->prepare("SELECT id FROM Usuarios WHERE id = ?");
        $stmt->execute([$usuario_id]);
        if (!$stmt->fetch()) {
            return [
                'success' => false,
                'message' => 'Usuario no válido',
                'folder_id' => null
            ];
        }

        // Si hay carpeta padre, verificar que existe
        if ($carpeta_padre_id !== null) {
            $stmt = $pdo->prepare("SELECT id FROM Carpetas WHERE id = ?");
            $stmt->execute([$carpeta_padre_id]);
            if (!$stmt->fetch()) {
                return [
                    'success' => false,
                    'message' => 'Carpeta padre no encontrada',
                    'folder_id' => null
                ];
            }
        }
        
        // MÉTODO ALTERNATIVO: Intentar una inserción directa para descartar problemas con la función
        try {
            $pdo->beginTransaction();
            
            $sqlDirect = "INSERT INTO Carpetas (nombre, carpeta_padre_id, usuario_id) 
                          VALUES (:nombre, :carpeta_padre_id, :usuario_id) 
                          RETURNING id";
            
            $stmtDirect = $pdo->prepare($sqlDirect);
            $stmtDirect->execute([
                ':nombre' => trim($nombre),
                ':carpeta_padre_id' => $carpeta_padre_id,
                ':usuario_id' => $usuario_id
            ]);
            
            $resultDirect = $stmtDirect->fetch(PDO::FETCH_ASSOC);
            $nueva_carpeta_id_direct = $resultDirect['id'];
            
            $pdo->commit();
            
            error_log("Inserción directa realizada con éxito, id: " . $nueva_carpeta_id_direct);
            
            return [
                'success' => true,
                'message' => 'Carpeta creada exitosamente (inserción directa)',
                'folder_id' => $nueva_carpeta_id_direct
            ];
            
        } catch (PDOException $directError) {
            $pdo->rollBack();
            error_log("Error en inserción directa: " . $directError->getMessage());
            
            // Si falla la inserción directa, intentamos con la función
            error_log("Intentando con la función crear_carpeta...");
        }
        
        // Llamar a la función PostgreSQL
        $sql = "SELECT crear_carpeta(:nombre, :carpeta_padre_id, :usuario_id) as new_id";
        $stmt = $pdo->prepare($sql);
        
        $params = [
            ':nombre' => trim($nombre),
            ':carpeta_padre_id' => $carpeta_padre_id,
            ':usuario_id' => $usuario_id
        ];
        
        error_log('Parámetros para crear_carpeta: ' . json_encode($params));
        
        $stmt->execute($params);
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        error_log('Resultado de la función SQL: ' . json_encode($result));
        
        if (!$result) {
            error_log('La función no devolvió ningún resultado');
            throw new Exception('La función crear_carpeta no devolvió ningún resultado');
        }
        
        $nueva_carpeta_id = $result['new_id'];
        
        if ($nueva_carpeta_id) {
            return [
                'success' => true,
                'message' => 'Carpeta creada exitosamente',
                'folder_id' => $nueva_carpeta_id
            ];
        } else {
            error_log('La función devolvió un ID nulo o inválido: ' . json_encode($result));
            throw new Exception('No se pudo crear la carpeta, no se obtuvo un ID válido');
        }
        
    } catch (PDOException $e) {
        error_log("Error detallado en crearCarpeta (PDOException): " . $e->getMessage());
        error_log("Código de error PDO: " . $e->getCode());
        
        // Información adicional sobre el error
        error_log("Error completo: " . var_export($e, true));
        
        if ($e->getCode() == '42883') {
            return [
                'success' => false,
                'message' => 'La función crear_carpeta no existe en la base de datos',
                'folder_id' => null
            ];
        }
        
        $mensaje = $e->getMessage();
        // Extraer mensaje amigable si es una excepción de PostgreSQL
        if (preg_match('/ERROR:\s*(.*?)(?:\n|$)/', $mensaje, $matches)) {
            $mensaje = $matches[1];
        }
        return [
            'success' => false,
            'message' => 'Error al crear la carpeta: ' . $mensaje,
            'folder_id' => null
        ];
    } catch (Exception $e) {
        error_log("Error general en crearCarpeta: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Error general al crear la carpeta: ' . $e->getMessage(),
            'folder_id' => null
        ];
    }
}