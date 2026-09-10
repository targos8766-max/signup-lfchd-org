CREATE TABLE event_slots (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    event_id BIGINT UNSIGNED NOT NULL,

    start_datetime DATETIME NOT NULL,
    end_datetime DATETIME NOT NULL,

    capacity SMALLINT UNSIGNED NOT NULL DEFAULT 1,

    enabled TINYINT(1) NOT NULL DEFAULT 1,

    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_event_slots_event
        FOREIGN KEY (event_id)
        REFERENCES events(id)
        ON DELETE CASCADE,

    UNIQUE KEY unique_event_start (
        event_id,
        start_datetime
    ),

    INDEX idx_event_slots_event (event_id),
    INDEX idx_event_slots_start (start_datetime),
    INDEX idx_event_slots_enabled (enabled)
) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;