-- Function to delete a folder and its contents
CREATE OR REPLACE FUNCTION eliminar_carpeta(
    p_carpeta_id INT,
    p_usuario_id INT
) RETURNS BOOLEAN AS $$
DECLARE
    v_tiene_permisos BOOLEAN;
BEGIN
    -- Verificar que la carpeta existe y pertenece al usuario
    SELECT EXISTS (
        SELECT 1 
        FROM Carpetas 
        WHERE id = p_carpeta_id 
        AND usuario_id = p_usuario_id
    ) INTO v_tiene_permisos;
    
    IF NOT v_tiene_permisos THEN
        RAISE EXCEPTION 'No tiene permisos para eliminar esta carpeta';
    END IF;
    
    -- Eliminar documentos asociados a la carpeta
    DELETE FROM Documentos_Carpetas 
    WHERE carpeta_id = p_carpeta_id;
    
    -- Eliminar subcarpetas recursivamente
    WITH RECURSIVE subcarpetas AS (
        SELECT id FROM Carpetas 
        WHERE carpeta_padre_id = p_carpeta_id
        UNION ALL
        SELECT c.id 
        FROM Carpetas c
        INNER JOIN subcarpetas s ON c.carpeta_padre_id = s.id
    )
    DELETE FROM Carpetas 
    WHERE id IN (SELECT id FROM subcarpetas);
    
    -- Eliminar la carpeta principal
    DELETE FROM Carpetas 
    WHERE id = p_carpeta_id 
    AND usuario_id = p_usuario_id;
    
    RETURN FOUND;
    
EXCEPTION
    WHEN OTHERS THEN
        RAISE NOTICE 'Error al eliminar la carpeta: %', SQLERRM;
        RETURN FALSE;
END;
$$ LANGUAGE plpgsql;