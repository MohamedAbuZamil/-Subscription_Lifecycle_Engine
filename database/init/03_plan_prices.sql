CREATE TABLE IF NOT EXISTS `plan_prices` (
    `id`                 INT UNSIGNED        NOT NULL AUTO_INCREMENT,
    `plan_id`            INT UNSIGNED        NOT NULL,
    `billing_cycle`      ENUM('monthly','yearly') NOT NULL,
    `currency`           CHAR(3)             NOT NULL,
    `price`              DECIMAL(10,2)       NOT NULL,
    `grace_period_days`  INT UNSIGNED        NOT NULL DEFAULT 3,
    `is_active`          TINYINT(1)          NOT NULL DEFAULT 1,
    `external_price_id`  VARCHAR(255)        NULL,
    `created_at`         DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`         DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_plan_billing_currency` (`plan_id`, `billing_cycle`, `currency`),
    CONSTRAINT `fk_plan_prices_plan_id` FOREIGN KEY (`plan_id`) REFERENCES `plans` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
