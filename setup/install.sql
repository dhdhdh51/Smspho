-- ============================================================
-- Private SMS Dashboard – Database Schema
-- Run this once during setup
-- ============================================================



-- ─── Users ───────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `users` (
  `id`            INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `google_id`     VARCHAR(100)    DEFAULT NULL,
  `name`          VARCHAR(150)    NOT NULL,
  `email`         VARCHAR(191)    NOT NULL,
  `password`      VARCHAR(255)    DEFAULT NULL COMMENT 'bcrypt hash, null for Google-only accounts',
  `profile_image` VARCHAR(500)    DEFAULT NULL,
  `api_key`       VARCHAR(100)    NOT NULL,
  `created_at`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_email`     (`email`),
  UNIQUE KEY `uk_api_key`   (`api_key`),
  KEY        `idx_google_id` (`google_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── SMS Messages ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `sms_messages` (
  `id`          INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `user_id`     INT UNSIGNED  NOT NULL,
  `sender`      VARCHAR(100)  NOT NULL,
  `message`     TEXT          NOT NULL,
  `device_name` VARCHAR(150)  DEFAULT NULL,
  `received_at` DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at`  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_sender`   (`user_id`, `sender`),
  KEY `idx_received_at`   (`received_at`),
  CONSTRAINT `fk_sms_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── Allowed Senders ──────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `allowed_senders` (
  `id`          INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `user_id`     INT UNSIGNED  NOT NULL,
  `sender_name` VARCHAR(100)  NOT NULL,
  `created_at`  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_user_sender` (`user_id`, `sender_name`),
  CONSTRAINT `fk_sender_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── Push Subscriptions ───────────────────────────────────────
CREATE TABLE IF NOT EXISTS `push_subscriptions` (
  `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`      INT UNSIGNED NOT NULL,
  `endpoint`     TEXT         NOT NULL,
  `p256dh_key`   VARCHAR(500) NOT NULL,
  `auth_key`     VARCHAR(500) NOT NULL,
  `created_at`   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_push_user` (`user_id`),
  CONSTRAINT `fk_push_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── Default Allowed Senders (examples) ──────────────────────
-- These will be inserted per-user upon first login; see install.php
