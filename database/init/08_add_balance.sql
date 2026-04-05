ALTER TABLE `users`
    ADD COLUMN `balance`      DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER `is_admin`,
    ADD COLUMN `auto_renewal` TINYINT(1)    NOT NULL DEFAULT 0    AFTER `balance`;
