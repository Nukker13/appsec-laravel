USE `oauth_lab`;

CREATE TABLE IF NOT EXISTS `users` (
    `id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `email`        VARCHAR(255)    NOT NULL,
    `password`     VARCHAR(255)    NOT NULL,
    `role`         ENUM('user','admin') NOT NULL DEFAULT 'user',
    `google_email` VARCHAR(255)    NULL DEFAULT NULL,
    `apple_email`  VARCHAR(255)    NULL DEFAULT NULL,
    `created_at`   TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_users_email`        (`email`),
    UNIQUE KEY `uq_users_google_email` (`google_email`),
    UNIQUE KEY `uq_users_apple_email`  (`apple_email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `reports` (
    `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `url`        VARCHAR(500)    NOT NULL,
    `created_at` TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
