
CREATE TABLE Registro (
    id SERIAL PRIMARY KEY,
    email VARCHAR(100) UNIQUE NOT NULL,
    contrasena VARCHAR(255) NOT NULL,
    codigo_verificacion VARCHAR(6) NOT NULL,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    estado VARCHAR(50) NOT NULL CHECK (estado IN ('pendiente', 'activado'))
);

-- Tabla: Usuarios
CREATE TABLE Usuarios (
    id SERIAL PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    contrasena VARCHAR(255) NOT NULL,
    rol VARCHAR(50) NOT NULL CHECK (rol IN ('admin', 'usuario')),
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabla: Tipos_Documentos
CREATE TABLE Tipos_Documentos (
    id SERIAL PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL
);

-- Tabla: Documentos
CREATE TABLE Documentos (
    id SERIAL PRIMARY KEY,
    titulo VARCHAR(255) NOT NULL,
    descripcion TEXT,
    ruta_archivo VARCHAR(255) NOT NULL,
    tipo_id INT NOT NULL,
    usuario_id INT NOT NULL,
    fecha_carga TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_tipo FOREIGN KEY (tipo_id) REFERENCES Tipos_Documentos(id),
    CONSTRAINT fk_usuario FOREIGN KEY (usuario_id) REFERENCES Usuarios(id)
);

-- Tabla: Carpetas
CREATE TABLE Carpetas (
    id SERIAL PRIMARY KEY,
    nombre VARCHAR(255) NOT NULL,
    carpeta_padre_id INT,
    usuario_id INT NOT NULL,
    CONSTRAINT fk_carpeta_padre FOREIGN KEY (carpeta_padre_id) REFERENCES Carpetas(id),
    CONSTRAINT fk_usuario_carpeta FOREIGN KEY (usuario_id) REFERENCES Usuarios(id)
);

-- Tabla: Documentos_Carpetas (relación muchos a muchos entre Documentos y Carpetas)
CREATE TABLE Documentos_Carpetas (
    id SERIAL PRIMARY KEY,
    documento_id INT NOT NULL,
    carpeta_id INT NOT NULL,
    CONSTRAINT fk_documento FOREIGN KEY (documento_id) REFERENCES Documentos(id),
    CONSTRAINT fk_carpeta FOREIGN KEY (carpeta_id) REFERENCES Carpetas(id)
);

-- Tabla: Permisos
CREATE TABLE Permisos (
    id SERIAL PRIMARY KEY,
    usuario_id INT NOT NULL,
    documento_id INT NOT NULL,
    permiso VARCHAR(50) NOT NULL CHECK (permiso IN ('lectura', 'escritura', 'eliminacion')),
    CONSTRAINT fk_usuario_permiso FOREIGN KEY (usuario_id) REFERENCES Usuarios(id),
    CONSTRAINT fk_documento_permiso FOREIGN KEY (documento_id) REFERENCES Documentos(id)
);

-- Índices para mejorar el rendimiento de búsquedas
CREATE INDEX idx_documentos_titulo ON Documentos(titulo);
CREATE INDEX idx_documentos_fecha_carga ON Documentos(fecha_carga);
CREATE INDEX idx_usuarios_email ON Usuarios(email);
CREATE INDEX idx_carpetas_nombre ON Carpetas(nombre);
CREATE INDEX idx_documentos_carpetas ON Documentos_Carpetas(documento_id, carpeta_id);

-- Insertar datos de ejemplo (opcional)
-- Insertar tipos de documentos
INSERT INTO Tipos_Documentos (nombre) VALUES
('Factura'),
('Informe'),
('Contrato');

-- Insertar usuarios
INSERT INTO Usuarios (nombre, email, contrasena, rol) VALUES
('Admin', 'admin@example.com', 'hashed_password_123', 'admin'),
('Usuario1', 'usuario1@example.com', 'hashed_password_456', 'usuario');

-- Insertar documentos
INSERT INTO Documentos (titulo, descripcion, ruta_archivo, tipo_id, usuario_id) VALUES
('Factura Enero 2023', 'Factura de ventas del mes de enero', '/ruta/factura_enero.pdf', 1, 1),
('Informe Anual 2023', 'Informe financiero del año 2023', '/ruta/informe_anual.pdf', 2, 2);

-- Insertar carpetas
INSERT INTO Carpetas (nombre, usuario_id) VALUES
('Finanzas', 1),
('Proyectos', 2);

-- Insertar permisos
INSERT INTO Permisos (usuario_id, documento_id, permiso) VALUES
(1, 1, 'escritura'),
(2, 2, 'lectura');

-- Insertar relación documentos-carpetas
INSERT INTO Documentos_Carpetas (documento_id, carpeta_id) VALUES
(1, 1),
(2, 2);