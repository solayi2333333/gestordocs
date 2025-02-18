CREATE OR REPLACE FUNCTION crear_carpeta(
    p_nombre VARCHAR(255),
    p_carpeta_padre_id INT,
    p_usuario_id INT
) RETURNS INT AS $$
DECLARE
    v_nueva_carpeta_id INT;
BEGIN
    -- Validar que el nombre no esté vacío
    IF p_nombre IS NULL OR trim(p_nombre) = '' THEN
        RAISE EXCEPTION 'El nombre de la carpeta no puede estar vacío';
    END IF;

    -- Validar que el usuario existe
    IF NOT EXISTS (SELECT 1 FROM Usuarios WHERE id = p_usuario_id) THEN
        RAISE EXCEPTION 'Usuario no encontrado';
    END IF;

    -- Validar que la carpeta padre existe si se proporciona
    IF p_carpeta_padre_id IS NOT NULL AND 
       NOT EXISTS (SELECT 1 FROM Carpetas WHERE id = p_carpeta_padre_id) THEN
        RAISE EXCEPTION 'Carpeta padre no encontrada';
    END IF;

    -- Insertar la nueva carpeta y obtener su ID
    INSERT INTO Carpetas (nombre, carpeta_padre_id, usuario_id)
    VALUES (p_nombre, p_carpeta_padre_id, p_usuario_id)
    RETURNING id INTO v_nueva_carpeta_id;
    
    RETURN v_nueva_carpeta_id;
    
EXCEPTION
    WHEN OTHERS THEN
        RAISE NOTICE 'Error al crear la carpeta: %', SQLERRM;
        RAISE;
END;
$$ LANGUAGE plpgsql;

--DROP FUNCTION crear_carpeta(character varying,integer,integer)7
select * from carpetas


--INSERT INTO Carpetas (nombre, carpeta_padre_id, usuario_id) 
--VALUES ('Prueba Manual', NULL, 1) 
--RETURNING id;