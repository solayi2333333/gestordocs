CREATE OR REPLACE FUNCTION listar_documentos(carpeta_id INT)
RETURNS TABLE(
    id INT,
    titulo VARCHAR,
    descripcion TEXT,
    ruta_archivo VARCHAR,
    fecha_carga TIMESTAMP,
    tipo_documento VARCHAR
) AS $$
BEGIN
    RETURN QUERY
    SELECT 
        d.id,
        d.titulo,
        d.descripcion,
        d.ruta_archivo,
        d.fecha_carga,
        t.nombre as tipo_documento
    FROM Documentos d
    INNER JOIN Documentos_Carpetas dc ON d.id = dc.documento_id
    INNER JOIN Tipos_Documentos t ON d.tipo_id = t.id
    WHERE dc.carpeta_id = $1;
END;
$$ LANGUAGE plpgsql;