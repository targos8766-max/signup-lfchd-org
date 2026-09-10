CREATE TABLE registration_reminder_log (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    registration_id BIGINT UNSIGNED NOT NULL,
    reminder_id BIGINT UNSIGNED NOT NULL,
    channel ENUM('sms', 'email') NOT NULL,
    status ENUM('sent', 'failed') NOT NULL,
    provider_message_id VARCHAR(255) NULL,
    error_message TEXT NULL,
    sent_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_registration_reminder_channel (
        registration_id,
        reminder_id,
        channel
    ),
    KEY idx_registration_reminder_log_registration_id (registration_id),
    KEY idx_registration_reminder_log_reminder_id (reminder_id),
    KEY idx_registration_reminder_log_status (status),
    CONSTRAINT fk_registration_reminder_log_registration
        FOREIGN KEY (registration_id)
        REFERENCES registrations(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_registration_reminder_log_reminder
        FOREIGN KEY (reminder_id)
        REFERENCES event_reminders(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
