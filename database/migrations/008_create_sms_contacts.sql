CREATE TABLE sms_contacts (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    phone_number VARCHAR(20) NOT NULL,
    sms_status ENUM('opted_in', 'opted_out') NOT NULL DEFAULT 'opted_in',
    opted_in_at DATETIME NULL,
    opted_out_at DATETIME NULL,
    last_opt_out_source VARCHAR(50) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_sms_contacts_phone_number (phone_number),
    KEY idx_sms_contacts_status (sms_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
