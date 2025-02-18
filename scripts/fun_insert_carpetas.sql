-- Función para crear una nueva carpeta
CREATE OR REPLACE FUNCTION crear_carpeta(
    p_nombre VARCHAR(255),
    p_carpeta_padre_id INT,
    p_usuario_id INT
) RETURNS INT AS $$
DECLARE
    v_nueva_carpeta_id INT;
BEGIN
    INSERT INTO Carpetas (nombre, carpeta_padre_id, usuario_id)
    VALUES (p_nombre, p_carpeta_padre_id, p_usuario_id)
    RETURNING id INTO v_nueva_carpeta_id;
    
    RETURN v_nueva_carpeta_id;
END;
$$ LANGUAGE plpgsql;