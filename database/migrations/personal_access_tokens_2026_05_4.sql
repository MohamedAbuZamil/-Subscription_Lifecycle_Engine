CREATE TABLE IF NOT EXISTS `personal_access_tokens` (
    `id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `tokenable_type` VARCHAR(255)    NOT NULL,
    `tokenable_id`   BIGINT UNSIGNED NOT NULL,
    `name`           VARCHAR(255)    NOT NULL,
    `token`          VARCHAR(64)     NOT NULL,
    `abilities`      TEXT            NULL,
    `last_used_at`   TIMESTAMP       NULL,
    `expires_at`     TIMESTAMP       NULL,
    `created_at`     TIMESTAMP       NULL,
    `updated_at`     TIMESTAMP       NULL,

    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_personal_access_tokens_token` (`token`),
    KEY `idx_tokenable` (`tokenable_type`, `tokenable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
