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

CREATE TABLE IF NOT EXISTS `reports` (
                                         `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                                         `url` VARCHAR(500) NOT NULL,
                                         `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                         PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
