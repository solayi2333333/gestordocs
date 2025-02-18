CREATE OR REPLACE FUNCTION listar_subcarpetas(carpeta_padre_id_param INT)
RETURNS TABLE(
    id INT, 
    nombre VARCHAR, 
    carpeta_padre_id INT
) AS $$
BEGIN
    RETURN QUERY
    SELECT 
        Carpetas.id, 
        Carpetas.nombre, 
        Carpetas.carpeta_padre_id
    FROM Carpetas
    WHERE Carpetas.carpeta_padre_id = carpeta_padre_id_param
    ORDER BY Carpetas.nombre ASC;
END;
$$ LANGUAGE plpgsql;