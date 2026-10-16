-- Runs once on first start of the mysql volume. The default database and user
-- come from the MYSQL_* env vars; this adds the PHPUnit database.
CREATE DATABASE IF NOT EXISTS `testing` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
GRANT ALL PRIVILEGES ON `testing`.* TO 'appsec'@'%';
FLUSH PRIVILEGES;
