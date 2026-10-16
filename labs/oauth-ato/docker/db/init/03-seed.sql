USE `oauth_lab`;

INSERT INTO `users` (`id`, `email`, `password`, `role`, `google_email`, `apple_email`, `created_at`)
VALUES
    (1, 'student@example.com', 'student123', 'user',  NULL, NULL, NOW()),
    (2, 'admin@example.com',   'admin123',   'admin', NULL, NULL, NOW());
