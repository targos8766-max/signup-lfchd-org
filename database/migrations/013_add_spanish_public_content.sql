ALTER TABLE events
    ADD COLUMN title_es VARCHAR(255) NULL AFTER title,
    ADD COLUMN description_es TEXT NULL AFTER description,
    ADD COLUMN location_es VARCHAR(255) NULL AFTER location;

ALTER TABLE event_questions
    ADD COLUMN question_text_es TEXT NULL AFTER question_text,
    ADD COLUMN options_json_es TEXT NULL AFTER options_json;
