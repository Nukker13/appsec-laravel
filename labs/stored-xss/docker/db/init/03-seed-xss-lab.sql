USE `xss_lab`;

-- Users
INSERT INTO `users` (`id`, `email`, `role`, `created_at`)
VALUES
    (1, 'student@example.com', 'user', NOW()),
    (2, 'admin@example.com',   'admin',   NOW());

-- Messages examples
INSERT INTO `messages` (user_id, `email`, `content`)
VALUES
    (2, 'admin@example.com', 'This is a test message'),
    (2, 'admin@example.com', '<b>Bold</b> text, with no JS'),
    (2, 'admin@example.com', 'Hey, just a heads-up - I added the /roleChange endpoint last week. No auth checks on the input yet, will harden later. For now it just takes user_id + role via POST.');