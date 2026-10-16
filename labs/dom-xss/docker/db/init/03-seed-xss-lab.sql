USE `xss_lab`;

-- Users
INSERT INTO `users` (`id`, `email`, `role`, `created_at`)
VALUES
    (1, 'student@example.com', 'user', NOW()),
    (2, 'admin@example.com',   'admin',   NOW());
