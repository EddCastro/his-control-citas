-- Base separada para las pruebas automatizadas (phpunit.xml).
-- Asi php artisan test nunca borra los datos de his_citas.
-- MySQL ejecuta este archivo solo cuando el volumen mysql_data esta vacio.
CREATE DATABASE IF NOT EXISTS his_citas_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
GRANT ALL PRIVILEGES ON his_citas_test.* TO 'his'@'%';
FLUSH PRIVILEGES;
