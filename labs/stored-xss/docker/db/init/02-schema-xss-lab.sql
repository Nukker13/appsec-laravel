USE `xss_lab`;

-- "users" table creation
CREATE TABLE IF NOT EXISTS `users` (
                                       `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                                       `email`      VARCHAR(255)    NOT NULL,
                                       `role`       ENUM('user','admin') NOT NULL DEFAULT 'user',
                                       `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                       PRIMARY KEY (`id`),
                                       UNIQUE KEY `uq_users_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- "messages" table creation (упрощённая структура)
CREATE TABLE IF NOT EXISTS `messages` (
                                          `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                                          `user_id`    BIGINT UNSIGNED NOT NULL,
                                          `email`      VARCHAR(255)    NOT NULL,         -- добавляем сразу email, чтобы не делать JOIN
                                          `content`    TEXT            NOT NULL,         -- храним сырые данные (уязвимость)
                                          `created_at` TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                          PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;