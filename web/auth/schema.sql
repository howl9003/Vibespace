-- Archspace authentication schema
-- Load with: mysql -u root -p Archspace < web/auth/schema.sql
-- (Database must already exist; this file does NOT create the database.)

SET NAMES utf8mb4;
SET time_zone = '+00:00';

-- ---------------------------------------------------------------------------
-- accounts: one row per registered player / admin
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `accounts` (
    `id`            INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `email`         VARCHAR(190)    NOT NULL,
    `password_hash` VARCHAR(255)    NOT NULL,
    `is_admin`      TINYINT(1)      NOT NULL DEFAULT 0,
    `created_at`    INT UNSIGNED    NOT NULL COMMENT 'Unix timestamp',
    `reset_token`   CHAR(64)        NULL     DEFAULT NULL COMMENT '64 hex chars, random',
    `reset_expires` INT UNSIGNED    NULL     DEFAULT NULL COMMENT 'Unix timestamp',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_accounts_email` (`email`)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- sessions: server-side session tokens (cookie value = id)
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `sessions` (
    `id`         CHAR(64)     NOT NULL COMMENT '64 hex chars, random',
    `account_id` INT UNSIGNED NOT NULL,
    `created_at` INT UNSIGNED NOT NULL COMMENT 'Unix timestamp',
    `expires`    INT UNSIGNED NOT NULL COMMENT 'Unix timestamp',
    PRIMARY KEY (`id`),
    KEY `idx_sessions_account_id` (`account_id`)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;
