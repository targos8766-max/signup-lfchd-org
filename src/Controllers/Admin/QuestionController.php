<?php

declare(strict_types=1);

namespace Boneblaze\SignupLfchdOrg\Controllers\Admin;

use Boneblaze\SignupLfchdOrg\Database\Database;

class QuestionController
{
    public function index(int $eventId): void
    {
        $pdo = Database::connection();

        $statement = $pdo->prepare('SELECT * FROM events WHERE id = ?');
        $statement->execute([$eventId]);
        $event = $statement->fetch();

        if (!$event) {
            http_response_code(404);
            echo '<h1>Event Not Found</h1>';
            exit;
        }

        $statement = $pdo->prepare(
            'SELECT * FROM event_question_sections
             WHERE event_id = ?
             ORDER BY sort_order, id'
        );
        $statement->execute([$eventId]);
        $sections = $statement->fetchAll();

        $statement = $pdo->prepare(
            'SELECT * FROM event_questions
             WHERE event_id = ?
             ORDER BY sort_order, id'
        );
        $statement->execute([$eventId]);
        $questions = $statement->fetchAll();

        $conditionQuestions = array_filter(
            $questions,
            fn (array $question) =>
                in_array($question['question_type'], ['select', 'checkbox'], true)
        );

        require dirname(__DIR__, 3) . '/templates/admin/events/questions.php';
    }

    public function store(int $eventId): void
    {
        $questionText = trim((string) ($_POST['question_text'] ?? ''));
        $questionTextEs = trim((string) ($_POST['question_text_es'] ?? ''));
        $questionType = (string) ($_POST['question_type'] ?? 'text');
        $dataType = $this->validatedDataType($questionType);
        $validationJson = $this->buildValidationJson($dataType);
        $required = isset($_POST['required']) ? 1 : 0;
        $sectionId = $this->validatedSectionId($eventId);
        $optionsRaw = trim((string) ($_POST['options'] ?? ''));
        $optionsRawEs = trim((string) ($_POST['options_es'] ?? ''));

        [$conditionalQuestionId, $conditionalOperator, $conditionalValue] =
            $this->conditionalSettings();

        [$blocksRegistration, $blockingOperator, $blockingValue,
            $blockingMessage, $blockingMessageEs] =
            $this->blockingSettings();

        $allowedTypes = ['text', 'textarea', 'select', 'checkbox'];

        if ($questionText === '' || !in_array($questionType, $allowedTypes, true)) {
            $this->redirectError($eventId, 'Please enter a valid question.');
        }

        [$optionsJson, $optionsJsonEs, $optionsError] =
            $this->buildOptions($questionType, $optionsRaw, $optionsRawEs);

        if ($optionsError !== null) {
            $this->redirectError($eventId, $optionsError);
        }

        $blockingError = $this->validateBlockingRule(
            $blocksRegistration,
            $questionType,
            $optionsJson,
            $blockingOperator,
            $blockingValue,
            $blockingMessage
        );

        if ($blockingError !== null) {
            $this->redirectError($eventId, $blockingError);
        }

        $pdo = Database::connection();

        $statement = $pdo->prepare(
            'SELECT COALESCE(MAX(sort_order), 0) + 10
             FROM event_questions WHERE event_id = ?'
        );
        $statement->execute([$eventId]);
        $sortOrder = (int) $statement->fetchColumn();

        $statement = $pdo->prepare(
            'INSERT INTO event_questions
                (
                    event_id, section_id, question_text, question_text_es,
                    question_type, data_type, validation_json,
                    options_json, options_json_es, required,
                    sort_order, enabled, conditional_question_id,
                    conditional_operator, conditional_value,
                    blocks_registration, blocking_operator, blocking_value,
                    blocking_message, blocking_message_es
                )
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?, ?, ?, ?, ?, ?, ?, ?)'
        );

        $statement->execute([
            $eventId,
            $sectionId,
            $questionText,
            $questionTextEs !== '' ? $questionTextEs : null,
            $questionType,
            $dataType,
            $validationJson,
            $optionsJson,
            $optionsJsonEs,
            $required,
            $sortOrder,
            $conditionalQuestionId,
            $conditionalOperator,
            $conditionalValue,
            $blocksRegistration,
            $blockingOperator,
            $blockingValue,
            $blockingMessage,
            $blockingMessageEs,
        ]);

        header('Location: /admin/events/' . $eventId . '/questions?created=1');
        exit;
    }

    public function update(int $eventId, int $questionId): void
    {
        $questionText = trim((string) ($_POST['question_text'] ?? ''));
        $questionTextEs = trim((string) ($_POST['question_text_es'] ?? ''));
        $questionType = (string) ($_POST['question_type'] ?? 'text');
        $dataType = $this->validatedDataType($questionType);
        $validationJson = $this->buildValidationJson($dataType);
        $required = isset($_POST['required']) ? 1 : 0;
        $enabled = isset($_POST['enabled']) ? 1 : 0;
        $sortOrder = max(0, (int) ($_POST['sort_order'] ?? 0));
        $sectionId = $this->validatedSectionId($eventId);
        $optionsRaw = trim((string) ($_POST['options'] ?? ''));
        $optionsRawEs = trim((string) ($_POST['options_es'] ?? ''));

        [$conditionalQuestionId, $conditionalOperator, $conditionalValue] =
            $this->conditionalSettings();

        if ($conditionalQuestionId !== null && $conditionalQuestionId === $questionId) {
            $conditionalQuestionId = null;
            $conditionalOperator = null;
            $conditionalValue = null;
        }

        [$blocksRegistration, $blockingOperator, $blockingValue,
            $blockingMessage, $blockingMessageEs] =
            $this->blockingSettings();

        $allowedTypes = ['text', 'textarea', 'select', 'checkbox'];

        if ($questionText === '' || !in_array($questionType, $allowedTypes, true)) {
            $this->redirectError($eventId, 'Please enter a valid question.');
        }

        [$optionsJson, $optionsJsonEs, $optionsError] =
            $this->buildOptions($questionType, $optionsRaw, $optionsRawEs);

        if ($optionsError !== null) {
            $this->redirectError($eventId, $optionsError);
        }

        $blockingError = $this->validateBlockingRule(
            $blocksRegistration,
            $questionType,
            $optionsJson,
            $blockingOperator,
            $blockingValue,
            $blockingMessage
        );

        if ($blockingError !== null) {
            $this->redirectError($eventId, $blockingError);
        }

        $pdo = Database::connection();
        $statement = $pdo->prepare(
            'UPDATE event_questions SET
                section_id = ?,
                question_text = ?,
                question_text_es = ?,
                question_type = ?,
                data_type = ?,
                validation_json = ?,
                options_json = ?,
                options_json_es = ?,
                required = ?,
                sort_order = ?,
                enabled = ?,
                conditional_question_id = ?,
                conditional_operator = ?,
                conditional_value = ?,
                blocks_registration = ?,
                blocking_operator = ?,
                blocking_value = ?,
                blocking_message = ?,
                blocking_message_es = ?
             WHERE id = ? AND event_id = ?'
        );

        $statement->execute([
            $sectionId,
            $questionText,
            $questionTextEs !== '' ? $questionTextEs : null,
            $questionType,
            $dataType,
            $validationJson,
            $optionsJson,
            $optionsJsonEs,
            $required,
            $sortOrder,
            $enabled,
            $conditionalQuestionId,
            $conditionalOperator,
            $conditionalValue,
            $blocksRegistration,
            $blockingOperator,
            $blockingValue,
            $blockingMessage,
            $blockingMessageEs,
            $questionId,
            $eventId,
        ]);

        header('Location: /admin/events/' . $eventId . '/questions?saved=1');
        exit;
    }

    public function copyQuestion(int $eventId, int $questionId): void
    {
        $pdo = Database::connection();
        $this->requireEvent($eventId);

        $statement = $pdo->prepare(
            'SELECT * FROM event_questions WHERE id = ? AND event_id = ?'
        );
        $statement->execute([$questionId, $eventId]);
        $question = $statement->fetch();

        if (!$question) {
            $this->redirectError($eventId, 'Question not found.');
        }

        $destinationSectionId = $this->validatedSectionId($eventId);

        $statement = $pdo->prepare(
            'SELECT COALESCE(MAX(sort_order), 0) + 10
             FROM event_questions
             WHERE event_id = ?
               AND ((section_id = ?) OR (section_id IS NULL AND ? IS NULL))'
        );
        $statement->execute([
            $eventId,
            $destinationSectionId,
            $destinationSectionId,
        ]);
        $sortOrder = (int) $statement->fetchColumn();

        $statement = $pdo->prepare(
            'INSERT INTO event_questions
                (
                    event_id, section_id, question_text, question_text_es,
                    question_type, data_type, validation_json,
                    match_registration_field, match_question_id,
                    options_json, options_json_es, required,
                    sort_order, enabled, conditional_question_id,
                    conditional_operator, conditional_value,
                    blocks_registration, blocking_operator, blocking_value,
                    blocking_message, blocking_message_es
                )
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );

        $statement->execute([
            $eventId,
            $destinationSectionId,
            $question['question_text'],
            $question['question_text_es'],
            $question['question_type'],
            $question['data_type'] ?? 'text',
            $question['validation_json'] ?? null,
            $question['match_registration_field'] ?? null,
            $question['match_question_id'] ?? null,
            $question['options_json'],
            $question['options_json_es'],
            (int) $question['required'],
            $sortOrder,
            (int) $question['enabled'],
            $question['conditional_question_id'],
            $question['conditional_operator'],
            $question['conditional_value'],
            (int) $question['blocks_registration'],
            $question['blocking_operator'],
            $question['blocking_value'],
            $question['blocking_message'],
            $question['blocking_message_es'],
        ]);

        header(
            'Location: /admin/events/' . $eventId
            . '/questions?question_copied=1'
        );
        exit;
    }

    public function deleteQuestion(int $eventId, int $questionId): void
    {
        $pdo = Database::connection();
        $this->requireEvent($eventId);

        $statement = $pdo->prepare(
            'SELECT question_text FROM event_questions
             WHERE id = ? AND event_id = ?'
        );
        $statement->execute([$questionId, $eventId]);
        $question = $statement->fetch();

        if (!$question) {
            $this->redirectError($eventId, 'Question not found.');
        }

        /*
         * registration_answers has ON DELETE CASCADE. Do not silently erase
         * historical registration data. Once a question has answers, the
         * administrator must disable it instead of deleting it.
         */
        $statement = $pdo->prepare(
            'SELECT COUNT(*) FROM registration_answers WHERE question_id = ?'
        );
        $statement->execute([$questionId]);
        $answerCount = (int) $statement->fetchColumn();

        if ($answerCount > 0) {
            $this->redirectError(
                $eventId,
                'This question already has registration answers and cannot be deleted. Disable the question instead so historical answers are preserved.'
            );
        }

        $pdo->beginTransaction();

        try {
            /*
             * These references are also protected by foreign keys, but
             * clearing them explicitly makes the intended behavior clear.
             */
            $statement = $pdo->prepare(
                'UPDATE event_questions
                 SET conditional_question_id = NULL,
                     conditional_operator = NULL,
                     conditional_value = NULL
                 WHERE event_id = ? AND conditional_question_id = ?'
            );
            $statement->execute([$eventId, $questionId]);

            $statement = $pdo->prepare(
                'UPDATE event_questions
                 SET match_question_id = NULL
                 WHERE event_id = ? AND match_question_id = ?'
            );
            $statement->execute([$eventId, $questionId]);

            $statement = $pdo->prepare(
                'DELETE FROM event_questions WHERE id = ? AND event_id = ?'
            );
            $statement->execute([$questionId, $eventId]);

            $pdo->commit();
        } catch (\Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $this->redirectError(
                $eventId,
                'The question could not be deleted.'
            );
        }

        header(
            'Location: /admin/events/' . $eventId
            . '/questions?question_deleted=1'
        );
        exit;
    }

    public function storeSection(int $eventId): void
    {
        $title = trim((string) ($_POST['title'] ?? ''));
        $titleEs = trim((string) ($_POST['title_es'] ?? ''));
        $description = trim((string) ($_POST['description'] ?? ''));
        $descriptionEs = trim((string) ($_POST['description_es'] ?? ''));

        if ($title === '') {
            $this->redirectError($eventId, 'Please enter an English section title.');
        }

        $pdo = Database::connection();
        $this->requireEvent($eventId);

        $statement = $pdo->prepare(
            'SELECT COALESCE(MAX(sort_order), 0) + 10
             FROM event_question_sections WHERE event_id = ?'
        );
        $statement->execute([$eventId]);
        $sortOrder = (int) $statement->fetchColumn();

        $statement = $pdo->prepare(
            'INSERT INTO event_question_sections
                (event_id, title, title_es, description, description_es, sort_order, enabled)
             VALUES (?, ?, ?, ?, ?, ?, 1)'
        );
        $statement->execute([
            $eventId,
            $title,
            $titleEs !== '' ? $titleEs : null,
            $description !== '' ? $description : null,
            $descriptionEs !== '' ? $descriptionEs : null,
            $sortOrder,
        ]);

        header('Location: /admin/events/' . $eventId . '/questions?section_created=1');
        exit;
    }

    public function updateSection(int $eventId, int $sectionId): void
    {
        $title = trim((string) ($_POST['title'] ?? ''));
        $titleEs = trim((string) ($_POST['title_es'] ?? ''));
        $description = trim((string) ($_POST['description'] ?? ''));
        $descriptionEs = trim((string) ($_POST['description_es'] ?? ''));
        $sortOrder = max(0, (int) ($_POST['sort_order'] ?? 0));
        $enabled = isset($_POST['enabled']) ? 1 : 0;

        if ($title === '') {
            $this->redirectError($eventId, 'Please enter an English section title.');
        }

        $pdo = Database::connection();
        $statement = $pdo->prepare(
            'UPDATE event_question_sections SET
                title = ?, title_es = ?, description = ?, description_es = ?,
                sort_order = ?, enabled = ?
             WHERE id = ? AND event_id = ?'
        );
        $statement->execute([
            $title,
            $titleEs !== '' ? $titleEs : null,
            $description !== '' ? $description : null,
            $descriptionEs !== '' ? $descriptionEs : null,
            $sortOrder,
            $enabled,
            $sectionId,
            $eventId,
        ]);

        header('Location: /admin/events/' . $eventId . '/questions?section_saved=1');
        exit;
    }

    public function deleteSection(int $eventId, int $sectionId): void
    {
        $pdo = Database::connection();
        $statement = $pdo->prepare(
            'DELETE FROM event_question_sections WHERE id = ? AND event_id = ?'
        );
        $statement->execute([$sectionId, $eventId]);

        header('Location: /admin/events/' . $eventId . '/questions?section_deleted=1');
        exit;
    }

    public function duplicateSection(int $eventId, int $sectionId): void
    {
        $pdo = Database::connection();
        $this->requireEvent($eventId);

        $statement = $pdo->prepare(
            'SELECT * FROM event_question_sections
             WHERE id = ? AND event_id = ?'
        );
        $statement->execute([$sectionId, $eventId]);
        $section = $statement->fetch();

        if (!$section) {
            $this->redirectError($eventId, 'Section not found.');
        }

        $statement = $pdo->prepare(
            'SELECT * FROM event_questions
             WHERE event_id = ? AND section_id = ?
             ORDER BY sort_order, id'
        );
        $statement->execute([$eventId, $sectionId]);
        $questions = $statement->fetchAll();

        $pdo->beginTransaction();

        try {
            $statement = $pdo->prepare(
                'SELECT COALESCE(MAX(sort_order), 0) + 10
                 FROM event_question_sections WHERE event_id = ?'
            );
            $statement->execute([$eventId]);
            $newSectionOrder = (int) $statement->fetchColumn();

            $statement = $pdo->prepare(
                'INSERT INTO event_question_sections
                    (
                        event_id, title, title_es, description,
                        description_es, sort_order, enabled
                    )
                 VALUES (?, ?, ?, ?, ?, ?, ?)'
            );
            $statement->execute([
                $eventId,
                (string) $section['title'] . ' - Copy',
                $section['title_es']
                    ? (string) $section['title_es'] . ' - Copy'
                    : null,
                $section['description'],
                $section['description_es'],
                $newSectionOrder,
                (int) $section['enabled'],
            ]);

            $newSectionId = (int) $pdo->lastInsertId();
            $questionMap = [];

            $insertQuestion = $pdo->prepare(
                'INSERT INTO event_questions
                    (
                        event_id, section_id, question_text, question_text_es,
                        question_type, data_type, validation_json,
                        match_registration_field, match_question_id,
                        options_json, options_json_es, required,
                        sort_order, enabled, conditional_question_id,
                        conditional_operator, conditional_value,
                        blocks_registration, blocking_operator, blocking_value,
                        blocking_message, blocking_message_es
                    )
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, NULL, ?, ?, ?, ?, ?, NULL, ?, ?, ?, ?, ?, ?, ?)'
            );

            /*
             * First pass creates all copied questions. Internal question
             * references are remapped after every new ID is known.
             */
            foreach ($questions as $question) {
                $insertQuestion->execute([
                    $eventId,
                    $newSectionId,
                    $question['question_text'],
                    $question['question_text_es'],
                    $question['question_type'],
                    $question['data_type'] ?? 'text',
                    $question['validation_json'] ?? null,
                    $question['match_registration_field'] ?? null,
                    $question['options_json'],
                    $question['options_json_es'],
                    (int) $question['required'],
                    (int) $question['sort_order'],
                    (int) $question['enabled'],
                    $question['conditional_operator'],
                    $question['conditional_value'],
                    (int) $question['blocks_registration'],
                    $question['blocking_operator'],
                    $question['blocking_value'],
                    $question['blocking_message'],
                    $question['blocking_message_es'],
                ]);

                $questionMap[(int) $question['id']] =
                    (int) $pdo->lastInsertId();
            }

            $updateReferences = $pdo->prepare(
                'UPDATE event_questions
                 SET conditional_question_id = ?,
                     match_question_id = ?
                 WHERE id = ? AND event_id = ?'
            );

            foreach ($questions as $question) {
                $oldConditionalId =
                    $question['conditional_question_id'] !== null
                        ? (int) $question['conditional_question_id']
                        : null;

                $oldMatchId =
                    ($question['match_question_id'] ?? null) !== null
                        ? (int) $question['match_question_id']
                        : null;

                /*
                 * References to questions inside the duplicated section point
                 * to their copies. References outside the section continue to
                 * point to the original external question.
                 */
                $newConditionalId =
                    $oldConditionalId !== null
                    && isset($questionMap[$oldConditionalId])
                        ? $questionMap[$oldConditionalId]
                        : $oldConditionalId;

                $newMatchId =
                    $oldMatchId !== null
                    && isset($questionMap[$oldMatchId])
                        ? $questionMap[$oldMatchId]
                        : $oldMatchId;

                $updateReferences->execute([
                    $newConditionalId,
                    $newMatchId,
                    $questionMap[(int) $question['id']],
                    $eventId,
                ]);
            }

            $pdo->commit();
        } catch (\Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $this->redirectError(
                $eventId,
                'The section could not be duplicated.'
            );
        }

        header(
            'Location: /admin/events/' . $eventId
            . '/questions?section_duplicated=1'
        );
        exit;
    }

    private function validatedDataType(string $questionType): string
    {
        $dataType = (string) ($_POST['data_type'] ?? 'text');
        $allowed = ['text', 'email', 'phone', 'date', 'birthdate', 'number'];

        if (!in_array($questionType, ['text', 'textarea'], true)) {
            return 'text';
        }

        return in_array($dataType, $allowed, true) ? $dataType : 'text';
    }

    private function buildValidationJson(string $dataType): ?string
    {
        if ($dataType !== 'birthdate') {
            return null;
        }

        $validation = [];
        foreach (['min_age', 'max_age'] as $field) {
            $raw = trim((string) ($_POST[$field] ?? ''));
            if ($raw === '') {
                continue;
            }

            if (
                filter_var($raw, FILTER_VALIDATE_INT) === false
                || (int) $raw < 0
                || (int) $raw > 150
            ) {
                continue;
            }

            $validation[$field] = (int) $raw;
        }

        if (
            isset($validation['min_age'], $validation['max_age'])
            && $validation['min_age'] > $validation['max_age']
        ) {
            [$validation['min_age'], $validation['max_age']] = [
                $validation['max_age'],
                $validation['min_age'],
            ];
        }

        return $validation === []
            ? null
            : json_encode($validation, JSON_UNESCAPED_UNICODE);
    }

    private function validatedSectionId(int $eventId): ?int
    {
        $raw = $_POST['section_id'] ?? '';

        if ($raw === '' || $raw === null) {
            return null;
        }

        $sectionId = (int) $raw;
        if ($sectionId <= 0) {
            return null;
        }

        $pdo = Database::connection();
        $statement = $pdo->prepare(
            'SELECT id FROM event_question_sections WHERE id = ? AND event_id = ?'
        );
        $statement->execute([$sectionId, $eventId]);

        if (!$statement->fetchColumn()) {
            $this->redirectError($eventId, 'The selected question section is not valid for this event.');
        }

        return $sectionId;
    }

    private function requireEvent(int $eventId): void
    {
        $pdo = Database::connection();
        $statement = $pdo->prepare('SELECT id FROM events WHERE id = ?');
        $statement->execute([$eventId]);

        if (!$statement->fetchColumn()) {
            http_response_code(404);
            echo '<h1>Event Not Found</h1>';
            exit;
        }
    }

    private function conditionalSettings(): array
    {
        $questionId =
            isset($_POST['conditional_question_id'])
            && $_POST['conditional_question_id'] !== ''
                ? (int) $_POST['conditional_question_id']
                : null;

        $operator = $_POST['conditional_operator'] ?? null;
        $value = isset($_POST['conditional_value'])
            ? trim((string) $_POST['conditional_value'])
            : null;

        if (
            $operator !== null
            && $operator !== ''
            && !in_array($operator, ['equals', 'not_equals'], true)
        ) {
            $operator = null;
        }

        if ($questionId === null) {
            $operator = null;
            $value = null;
        }

        if ($value === '') {
            $value = null;
        }

        return [$questionId, $operator, $value];
    }

    private function blockingSettings(): array
    {
        if (!isset($_POST['blocks_registration'])) {
            return [0, null, null, null, null];
        }

        $operator = (string) ($_POST['blocking_operator'] ?? 'equals');
        if (!in_array($operator, ['equals', 'not_equals'], true)) {
            $operator = 'equals';
        }

        $value = trim((string) ($_POST['blocking_value'] ?? ''));
        $message = trim((string) ($_POST['blocking_message'] ?? ''));
        $messageEs = trim((string) ($_POST['blocking_message_es'] ?? ''));

        return [
            1,
            $operator,
            $value !== '' ? $value : null,
            $message !== '' ? $message : null,
            $messageEs !== '' ? $messageEs : null,
        ];
    }

    private function validateBlockingRule(
        int $enabled,
        string $questionType,
        ?string $optionsJson,
        ?string $operator,
        ?string $value,
        ?string $message
    ): ?string {
        if ($enabled !== 1) {
            return null;
        }

        if ($operator === null || $value === null) {
            return 'Registration Qualification requires a condition and value.';
        }

        if ($message === null) {
            return 'Registration Qualification requires an English blocking message.';
        }

        if ($questionType === 'checkbox' && $optionsJson === null) {
            if (!in_array($value, ['0', '1'], true)) {
                return 'For a single checkbox qualification rule, use 1 for checked or 0 for unchecked.';
            }
            return null;
        }

        if (in_array($questionType, ['select', 'checkbox'], true)) {
            $options = json_decode($optionsJson ?? '[]', true);
            if (
                is_array($options)
                && !in_array($value, array_map('strval', $options), true)
            ) {
                return 'The Registration Qualification value must exactly match an English option.';
            }
        }

        return null;
    }

    private function redirectError(int $eventId, string $message): never
    {
        header(
            'Location: /admin/events/' . $eventId
            . '/questions?error=' . urlencode($message)
        );
        exit;
    }

    private function buildOptions(
        string $questionType,
        string $optionsRaw,
        string $optionsRawEs
    ): array {
        if (!in_array($questionType, ['select', 'checkbox'], true)) {
            return [null, null, null];
        }

        $options = $this->parseOptions($optionsRaw);
        $optionsEs = $this->parseOptions($optionsRawEs);

        if ($questionType === 'select' && count($options) < 2) {
            return [null, null, 'Dropdown questions need at least two English options.'];
        }

        if ($questionType === 'checkbox' && $options === [] && $optionsEs !== []) {
            return [
                null,
                null,
                'Enter the English checkbox options before adding Spanish translations.',
            ];
        }

        if ($optionsEs !== [] && count($optionsEs) !== count($options)) {
            return [
                null,
                null,
                'Spanish options must have the same number of lines as the English options.',
            ];
        }

        return [
            $options !== [] ? json_encode($options, JSON_UNESCAPED_UNICODE) : null,
            $optionsEs !== [] ? json_encode($optionsEs, JSON_UNESCAPED_UNICODE) : null,
            null,
        ];
    }

    private function parseOptions(string $optionsRaw): array
    {
        return array_values(
            array_filter(
                array_map('trim', preg_split('/\r\n|\r|\n/', $optionsRaw)),
                fn ($value) => $value !== ''
            )
        );
    }
}
