CREATE OR REPLACE FUNCTION listar_carpetas(usuario_id INT)
RETURNS TABLE(id INT, nombre VARCHAR, carpeta_padre_id INT) AS $$
BEGIN
    RETURN QUERY
    SELECT Carpetas.id, Carpetas.nombre, Carpetas.carpeta_padre_id
    FROM Carpetas
    WHERE Carpetas.usuario_id = $1;
END;
$$ LANGUAGE plpgsql;


INSERT INTO Carpetas (nombre, usuario_id, carpeta_padre_id) VALUES
('Sol', 1, NULL) -- Carpeta raíz