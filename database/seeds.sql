-- Datos de ejemplo para la base de datos demoPHP
-- Asegúrate de haber ejecutado primero schema.sql
-- y de estar usando la BD correcta:
--   USE demoPHP;

INSERT INTO roles (id, name, description)
VALUES
  (1, 'admin', 'Administrador del sistema'),
  (2, 'user', 'Usuario estándar'),
  (3, 'dummy', 'Rol de prueba');

-- NOTA: los passwords deben ser hashes bcrypt generados con password_hash en PHP.
-- Estos son ejemplos de prueba. Ajusta si quieres contraseñas específicas.

INSERT INTO users (id, first_name, last_name, middle_name, email, password, role_id, status)
VALUES
  (1, 'User', 'One', NULL, 'user1@example.com', '$2y$10$Qg4fgQxOx9lTec7Ne0ogfuvInvNb10MbOjlRpKHW85WWgsLI120TO', 1, 'active'),
  (2, 'User', 'Two', NULL, 'user2@example.com', '$2y$10$Qg4fgQxOx9lTec7Ne0ogfuvInvNb10MbOjlRpKHW85WWgsLI120TO', 2, 'active'),
  (3, 'User', 'Three', NULL, 'user3@example.com', '$2y$10$V3rjLn//GG6vYigD9HyrjuLah2TwghH.dQ/D7os/wZEqf9zmdozMi', 3, 'inactive');