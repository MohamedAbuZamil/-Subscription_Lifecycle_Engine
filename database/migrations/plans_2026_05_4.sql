CREATE TABLE IF NOT EXISTS `plans` (
    `id`          INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `code`        VARCHAR(64)     NOT NULL,
    `name`        VARCHAR(255)    NOT NULL,
    `description` TEXT            NULL,
    `trial_days`  INT UNSIGNED    NOT NULL DEFAULT 0,
    `is_active`   TINYINT(1)      NOT NULL DEFAULT 1,
    `created_at`  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_plans_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
