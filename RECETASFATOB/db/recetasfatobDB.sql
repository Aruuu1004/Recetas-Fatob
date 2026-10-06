-- Crear la base de datos (si no existe) y usarla
CREATE DATABASE IF NOT EXISTS recetas_fatob;
USE recetas_fatob;

-- 1. Tabla ROLES (Requerida por la FK de USUARIO)
CREATE TABLE IF NOT EXISTS ROLES (
    id_rol INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL,
    acceso_datos_sensibles BOOLEAN DEFAULT FALSE,
    descripcion VARCHAR(255)
);

-- Insertar roles básicos por defecto
INSERT IGNORE INTO ROLES (id_rol, nombre, descripcion) VALUES
(1, 'Administrador', 'Control total del sistema'),
(2, 'Usuario', 'Usuario final registrado');

-- 2. Tabla USUARIO
CREATE TABLE IF NOT EXISTS USUARIO (
    id_usuario INT AUTO_INCREMENT PRIMARY KEY,
    id_rol INT NOT NULL DEFAULT 2,
    nombre VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    contrasena VARCHAR(255) NOT NULL,
    avatar VARCHAR(255) DEFAULT 'default_avatar.png',
    fecha_registro DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_rol) REFERENCES ROLES(id_rol) ON DELETE RESTRICT ON UPDATE CASCADE
);

-- 3. Tabla RESTRICCION_DIETARIA
CREATE TABLE IF NOT EXISTS RESTRICCION_DIETARIA (
    id_restriccion INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    tipo VARCHAR(50)
);

-- Insertar restricciones básicas por defecto
INSERT IGNORE INTO RESTRICCION_DIETARIA (id_restriccion, nombre, tipo) VALUES
(1, 'Celiaco', 'Alergia'),
(2, 'Intolerante a la Lactosa', 'Intolerancia'),
(3, 'Vegano', 'Dieta'),
(4, 'Vegetariano', 'Dieta');

-- 4. Tabla asociativa USUARIO_RESTRICCION
CREATE TABLE IF NOT EXISTS USUARIO_RESTRICCION (
    id_usuario INT NOT NULL,
    id_restriccion INT NOT NULL,
    PRIMARY KEY (id_usuario, id_restriccion),
    FOREIGN KEY (id_usuario) REFERENCES USUARIO(id_usuario) ON DELETE CASCADE,
    FOREIGN KEY (id_restriccion) REFERENCES RESTRICCION_DIETARIA(id_restriccion) ON DELETE CASCADE
);

-- 5. Tabla RECETA
CREATE TABLE IF NOT EXISTS RECETA (
    id_receta INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL,
    titulo VARCHAR(150) NOT NULL,
    descripcion VARCHAR(500) NOT NULL,
    ingredientes TEXT NOT NULL,
    instrucciones TEXT NOT NULL,
    tiempo_preparacion INT NOT NULL DEFAULT 0,
    imagen VARCHAR(255) DEFAULT NULL,
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_usuario) REFERENCES USUARIO(id_usuario) ON DELETE CASCADE
);

-- 6. Tabla COMENTARIO
CREATE TABLE IF NOT EXISTS COMENTARIO (
    id_comentario INT AUTO_INCREMENT PRIMARY KEY,
    id_receta INT NOT NULL,
    id_usuario INT NOT NULL,
    texto VARCHAR(1000) NOT NULL,
    fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_receta) REFERENCES RECETA(id_receta) ON DELETE CASCADE,
    FOREIGN KEY (id_usuario) REFERENCES USUARIO(id_usuario) ON DELETE CASCADE
);

-- 7. Tabla CALIFICACION (una por usuario y receta)
CREATE TABLE IF NOT EXISTS CALIFICACION (
    id_calificacion INT AUTO_INCREMENT PRIMARY KEY,
    id_receta INT NOT NULL,
    id_usuario INT NOT NULL,
    puntuacion TINYINT NOT NULL,
    fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_receta_usuario (id_receta, id_usuario),
    FOREIGN KEY (id_receta) REFERENCES RECETA(id_receta) ON DELETE CASCADE,
    FOREIGN KEY (id_usuario) REFERENCES USUARIO(id_usuario) ON DELETE CASCADE
);
