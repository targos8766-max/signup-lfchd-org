ALTER TABLE event_questions
    ADD COLUMN conditional_question_id
        BIGINT UNSIGNED NULL
        AFTER enabled,

    ADD COLUMN conditional_operator
        ENUM(
            'equals',
            'not_equals'
        )
        NULL
        AFTER conditional_question_id,

    ADD COLUMN conditional_value
        VARCHAR(255) NULL
        AFTER conditional_operator,

    ADD CONSTRAINT fk_event_questions_condition
        FOREIGN KEY (
            conditional_question_id
        )
        REFERENCES event_questions(id)
        ON DELETE SET NULL,

    ADD INDEX idx_event_questions_condition (
        conditional_question_id
    );
