CREATE TABLE IF NOT EXISTS `subscriptions` (
    `id`                        INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `user_id`                   INT UNSIGNED    NOT NULL,
    `plan_id`                   INT UNSIGNED    NOT NULL,
    `plan_price_id`             INT UNSIGNED    NOT NULL,
    `status`                    ENUM('pending','trialing','active','past_due','canceled','expired') NOT NULL DEFAULT 'pending',
    `started_at`                DATETIME        NOT NULL,
    `trial_starts_at`           DATETIME        NULL,
    `trial_ends_at`             DATETIME        NULL,
    `current_period_starts_at`  DATETIME        NULL,
    `current_period_ends_at`    DATETIME        NULL,
    `grace_period_ends_at`      DATETIME        NULL,
    `canceled_at`               DATETIME        NULL,
    `expires_at`                DATETIME        NULL,
    `created_at`                DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`                DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    KEY `idx_subscriptions_user_id` (`user_id`),
    KEY `idx_subscriptions_status` (`status`),
    CONSTRAINT `fk_subscriptions_plan_id`       FOREIGN KEY (`plan_id`)       REFERENCES `plans`       (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_subscriptions_plan_price_id` FOREIGN KEY (`plan_price_id`) REFERENCES `plan_prices` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
