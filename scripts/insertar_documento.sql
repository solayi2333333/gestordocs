-- Función para insertar un nuevo documento
CREATE OR REPLACE FUNCTION insertar_documento(
    p_titulo VARCHAR,
    p_descripcion TEXT,
    p_ruta_archivo VARCHAR,
    p_tipo_id INT,
    p_usuario_id INT,
    p_carpeta_id INT
) RETURNS INT AS $$
DECLARE
    v_documento_id INT;
BEGIN
    -- Insertar el documento
    INSERT INTO Documentos (
        titulo,
        descripcion,
        ruta_archivo,
        tipo_id,
        usuario_id,
        fecha_carga
    ) VALUES (
        p_titulo,
        p_descripcion,
        p_ruta_archivo,
        p_tipo_id,
        p_usuario_id,
        CURRENT_TIMESTAMP
    ) RETURNING id INTO v_documento_id;

    -- Relacionar el documento con la carpeta
    INSERT INTO Documentos_Carpetas (
        documento_id,
        carpeta_id
    ) VALUES (
        v_documento_id,
        p_carpeta_id
    );

    RETURN v_documento_id;
END;
$$ LANGUAGE plpgsql;

