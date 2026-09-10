CREATE TABLE registration_answers (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    registration_id BIGINT UNSIGNED NOT NULL,
    question_id BIGINT UNSIGNED NOT NULL,
    answer_text TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_registration_answers_registration
        FOREIGN KEY (registration_id)
        REFERENCES registrations(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_registration_answers_question
        FOREIGN KEY (question_id)
        REFERENCES event_questions(id)
        ON DELETE CASCADE,

    UNIQUE KEY unique_registration_question (
        registration_id,
        question_id
    ),

    INDEX idx_registration_answers_registration (
        registration_id
    ),

    INDEX idx_registration_answers_question (
        question_id
    )
) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;
