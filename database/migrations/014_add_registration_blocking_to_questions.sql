ALTER TABLE event_questions
    ADD COLUMN blocks_registration TINYINT(1) NOT NULL DEFAULT 0
        AFTER conditional_value,
    ADD COLUMN blocking_operator ENUM('equals', 'not_equals') NULL
        AFTER blocks_registration,
    ADD COLUMN blocking_value VARCHAR(500) NULL
        AFTER blocking_operator,
    ADD COLUMN blocking_message TEXT NULL
        AFTER blocking_value,
    ADD COLUMN blocking_message_es TEXT NULL
        AFTER blocking_message;
