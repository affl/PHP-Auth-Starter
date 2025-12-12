-- Estructura básica de la base de datos para el proyecto demoPHP
-- Crea primero la base de datos (o ajusta el nombre según tu entorno):
--   CREATE DATABASE demoPHP CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
--   USE demoPHP;

-- Tabla: roles
CREATE TABLE roles (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(50) NOT NULL,
  description VARCHAR(255) DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_roles_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla: users
CREATE TABLE users (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  first_name VARCHAR(100) NOT NULL,
  last_name VARCHAR(100) NOT NULL,
  middle_name VARCHAR(100) DEFAULT NULL,
  email VARCHAR(120) NOT NULL,
  password VARCHAR(255) NOT NULL,
  role_id INT UNSIGNED NOT NULL,
  status ENUM('active','inactive') NOT NULL DEFAULT 'active',
  remember_token_hash VARCHAR(255) DEFAULT NULL,
  remember_token_expires_at DATETIME DEFAULT NULL,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_users_email (email),
  KEY fk_user_role (role_id),
  CONSTRAINT fk_user_role FOREIGN KEY (role_id)
    REFERENCES roles (id)
    ON DELETE RESTRICT
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;