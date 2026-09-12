ALTER TABLE registrations
    ADD COLUMN preferred_language ENUM('en', 'es') NOT NULL DEFAULT 'en'
    AFTER sms_opt_in_at;
