CREATE TABLE IF NOT EXISTS `subscription_transactions` (
    `id`                      INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `subscription_id`         INT UNSIGNED    NOT NULL,
    `reference`               VARCHAR(255)    NOT NULL,
    `amount`                  DECIMAL(10,2)   NOT NULL,
    `currency`                CHAR(3)         NOT NULL,
    `status`                  ENUM('pending','paid','failed','refunded') NOT NULL DEFAULT 'pending',
    `provider`                VARCHAR(100)    NULL,
    `provider_transaction_id` VARCHAR(255)    NULL,
    `paid_at`                 DATETIME        NULL,
    `failed_at`               DATETIME        NULL,
    `failure_reason`          TEXT            NULL,
    `metadata`                JSON            NULL,
    `created_at`              DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`              DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_transactions_reference` (`reference`),
    KEY `idx_transactions_subscription_id` (`subscription_id`),
    KEY `idx_transactions_status` (`status`),
    CONSTRAINT `fk_transactions_subscription_id` FOREIGN KEY (`subscription_id`) REFERENCES `subscriptions` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
