ALTER TABLE registrations
    ADD COLUMN sms_opt_in TINYINT(1) NOT NULL DEFAULT 0 AFTER phone,
    ADD COLUMN sms_opt_in_at DATETIME NULL AFTER sms_opt_in;
