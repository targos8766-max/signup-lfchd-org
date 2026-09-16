-- Migration 015
-- Add reusable question sections and validation metadata.

CREATE TABLE event_question_sections (
    id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    event_id BIGINT(20) UNSIGNED NOT NULL,
    title VARCHAR(255) NOT NULL,
    title_es VARCHAR(255) DEFAULT NULL,
    description TEXT DEFAULT NULL,
    description_es TEXT DEFAULT NULL,
    sort_order SMALLINT(5) UNSIGNED NOT NULL DEFAULT 0,
    enabled TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP() ON UPDATE CURRENT_TIMESTAMP(),
    PRIMARY KEY (id),
    KEY idx_event_question_sections_event (event_id),
    KEY idx_event_question_sections_sort (event_id, sort_order),
    CONSTRAINT fk_event_question_sections_event
        FOREIGN KEY (event_id) REFERENCES events (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE event_questions
    ADD COLUMN section_id BIGINT(20) UNSIGNED DEFAULT NULL AFTER event_id,
    ADD COLUMN data_type VARCHAR(50) NOT NULL DEFAULT 'text' AFTER question_type,
    ADD COLUMN validation_json TEXT DEFAULT NULL AFTER data_type,
    ADD COLUMN match_registration_field VARCHAR(50) DEFAULT NULL AFTER validation_json,
    ADD COLUMN match_question_id BIGINT(20) UNSIGNED DEFAULT NULL AFTER match_registration_field,
    ADD KEY idx_event_questions_section (section_id),
    ADD KEY idx_event_questions_match_question (match_question_id),
    ADD CONSTRAINT fk_event_questions_section
        FOREIGN KEY (section_id) REFERENCES event_question_sections (id) ON DELETE SET NULL,
    ADD CONSTRAINT fk_event_questions_match_question
        FOREIGN KEY (match_question_id) REFERENCES event_questions (id) ON DELETE SET NULL;
