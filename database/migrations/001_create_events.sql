CREATE TABLE events (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    title VARCHAR(255) NOT NULL,
    public_slug VARCHAR(64) NOT NULL UNIQUE,

    description TEXT NULL,
    location VARCHAR(255) NULL,

    event_date DATE NOT NULL,

    start_time TIME NOT NULL,
    end_time TIME NOT NULL,

    interval_minutes SMALLINT UNSIGNED NOT NULL,
    default_capacity SMALLINT UNSIGNED NOT NULL DEFAULT 1,

    status ENUM(
        'draft',
        'open',
        'closed',
        'cancelled'
    ) NOT NULL DEFAULT 'draft',

    signup_open_at DATETIME NULL,
    signup_close_at DATETIME NULL,

    created_by VARCHAR(255) NULL,

    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_events_date (event_date),
    INDEX idx_events_status (status)
) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;