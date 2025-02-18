-- Function to edit a folder name
CREATE OR REPLACE FUNCTION editar_carpeta(
    p_carpeta_id INT,
    p_nuevo_nombre VARCHAR(255),
    p_usuario_id INT
) RETURNS BOOLEAN AS $$
DECLARE
    v_tiene_permisos BOOLEAN;
BEGIN
    -- Validar que el nombre no esté vacío
    IF p_nuevo_nombre IS NULL OR trim(p_nuevo_nombre) = '' THEN
        RAISE EXCEPTION 'El nombre de la carpeta no puede estar vacío';
    END IF;

    -- Verificar que la carpeta existe y pertenece al usuario
    SELECT EXISTS (
        SELECT 1 
        FROM Carpetas 
        WHERE id = p_carpeta_id 
        AND usuario_id = p_usuario_id
    ) INTO v_tiene_permisos;
    
    IF NOT v_tiene_permisos THEN
        RAISE EXCEPTION 'No tiene permisos para editar esta carpeta';
    END IF;
    
    -- Actualizar el nombre de la carpeta
    UPDATE Carpetas 
    SET nombre = p_nuevo_nombre
    WHERE id = p_carpeta_id 
    AND usuario_id = p_usuario_id;
    
    RETURN FOUND;
    
EXCEPTION
    WHEN OTHERS THEN
        RAISE NOTICE 'Error al editar la carpeta: %', SQLERRM;
        RETURN FALSE;
END;
$$ LANGUAGE plpgsql;