-- Data Verification Dashboard (Demo) - database schema
-- MySQL / MariaDB. All sample data is fictitious.

SET NAMES utf8mb4;
SET time_zone = '+00:00';

CREATE TABLE IF NOT EXISTS users (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name          VARCHAR(120) NOT NULL,
    username      VARCHAR(60)  NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role          ENUM('admin','verifikator','staff') NOT NULL DEFAULT 'staff',
    is_active     TINYINT(1)   NOT NULL DEFAULT 1,
    created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Each imported record. The UNIQUE key on nik is the core of
-- duplicate prevention: a repeated NIK can never be inserted twice.
CREATE TABLE IF NOT EXISTS records (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nik         CHAR(16)     NOT NULL,
    full_name   VARCHAR(160) NOT NULL,
    address     VARCHAR(255) DEFAULT NULL,
    category    VARCHAR(60)  DEFAULT NULL,
    status      ENUM('pending','verified','rejected') NOT NULL DEFAULT 'pending',
    source      VARCHAR(120) DEFAULT NULL,
    note        VARCHAR(500) DEFAULT NULL,
    created_by  INT UNSIGNED NOT NULL,
    verified_by INT UNSIGNED DEFAULT NULL,
    verified_at DATETIME     DEFAULT NULL,
    created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_records_nik (nik),
    KEY idx_records_status (status),
    CONSTRAINT fk_records_user FOREIGN KEY (created_by) REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- One row per CSV import, with a summary of the outcome.
CREATE TABLE IF NOT EXISTS import_batches (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    filename    VARCHAR(255) NOT NULL,
    user_id     INT UNSIGNED NOT NULL,
    total_rows  INT UNSIGNED NOT NULL DEFAULT 0,
    inserted    INT UNSIGNED NOT NULL DEFAULT 0,
    duplicates  INT UNSIGNED NOT NULL DEFAULT 0,
    invalid     INT UNSIGNED NOT NULL DEFAULT 0,
    created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    CONSTRAINT fk_batches_user FOREIGN KEY (user_id) REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Immutable audit trail for imports and verification actions.
CREATE TABLE IF NOT EXISTS audit_log (
    id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id    INT UNSIGNED NOT NULL,
    action     VARCHAR(40)  NOT NULL,
    entity     VARCHAR(40)  NOT NULL,
    entity_id  INT UNSIGNED DEFAULT NULL,
    detail     VARCHAR(500) DEFAULT NULL,
    created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_audit_entity (entity, entity_id),
    CONSTRAINT fk_audit_user FOREIGN KEY (user_id) REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
