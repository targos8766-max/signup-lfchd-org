CREATE TABLE registrations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    event_id BIGINT UNSIGNED NOT NULL,
    slot_id BIGINT UNSIGNED NOT NULL,

    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,

    email VARCHAR(255) NULL,
    phone VARCHAR(50) NULL,
    department VARCHAR(255) NULL,

    confirmation_code VARCHAR(64) NOT NULL UNIQUE,

    status ENUM(
        'confirmed',
        'cancelled'
    ) NOT NULL DEFAULT 'confirmed',

    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_registrations_event
        FOREIGN KEY (event_id)
        REFERENCES events(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_registrations_slot
        FOREIGN KEY (slot_id)
        REFERENCES event_slots(id)
        ON DELETE CASCADE,

    INDEX idx_registrations_event (event_id),
    INDEX idx_registrations_slot (slot_id),
    INDEX idx_registrations_status (status),
    INDEX idx_registrations_email (email)
) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;