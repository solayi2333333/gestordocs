-- Function to move a folder to a new parent folder
CREATE OR REPLACE FUNCTION mover_carpeta(
    p_carpeta_id INT,
    p_nuevo_padre_id INT,
    p_usuario_id INT
) RETURNS BOOLEAN AS $$
DECLARE
    v_tiene_permisos BOOLEAN;
    v_es_subcarpeta BOOLEAN;
BEGIN
    -- Verificar que la carpeta existe y pertenece al usuario
    SELECT EXISTS (
        SELECT 1 
        FROM Carpetas 
        WHERE id = p_carpeta_id 
        AND usuario_id = p_usuario_id
    ) INTO v_tiene_permisos;
    
    IF NOT v_tiene_permisos THEN
        RAISE EXCEPTION 'No tiene permisos para mover esta carpeta';
    END IF;
    
    -- Verificar que la carpeta destino existe (si no es null)
    IF p_nuevo_padre_id IS NOT NULL AND NOT EXISTS (
        SELECT 1 
        FROM Carpetas 
        WHERE id = p_nuevo_padre_id 
        AND usuario_id = p_usuario_id
    ) THEN
        RAISE EXCEPTION 'Carpeta destino no encontrada';
    END IF;
    
    -- Verificar que no estamos moviendo una carpeta dentro de sí misma o sus subcarpetas
    WITH RECURSIVE subcarpetas AS (
        SELECT id FROM Carpetas WHERE id = p_carpeta_id
        UNION ALL
        SELECT c.id 
        FROM Carpetas c
        INNER JOIN subcarpetas s ON c.carpeta_padre_id = s.id
    )
    SELECT EXISTS (
        SELECT 1 FROM subcarpetas WHERE id = p_nuevo_padre_id
    ) INTO v_es_subcarpeta;
    
    IF v_es_subcarpeta THEN
        RAISE EXCEPTION 'No se puede mover una carpeta dentro de sí misma o sus subcarpetas';
    END IF;
    
    -- Mover la carpeta
    UPDATE Carpetas 
    SET carpeta_padre_id = p_nuevo_padre_id
    WHERE id = p_carpeta_id 
    AND usuario_id = p_usuario_id;
    
    RETURN FOUND;
    
EXCEPTION
    WHEN OTHERS THEN
        RAISE NOTICE 'Error al mover la carpeta: %', SQLERRM;
        RETURN FALSE;
END;
$$ LANGUAGE plpgsql;