INSERT INTO `users` (`name`, `email`, `password`, `is_admin`, `created_at`, `updated_at`)
VALUES (
    'admin',
    'admin@admin.com',
    '$2y$10$TnZruSH1aQwjriYLraREgOAXSJt9ojiOjM4TXSDEEw6osIJvvv36m',
    1,
    NOW(),
    NOW()
)
ON DUPLICATE KEY UPDATE
    `name`       = VALUES(`name`),
    `password`   = VALUES(`password`),
    `is_admin`   = 1,
    `updated_at` = NOW();
