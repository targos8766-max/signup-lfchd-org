<?php

declare(strict_types=1);

namespace Boneblaze\SignupLfchdOrg\Controllers\Admin;

use Boneblaze\SignupLfchdOrg\Database\Database;

class QuestionController
{
    public function index(int $eventId): void
    {
        $pdo = Database::connection();

        $statement = $pdo->prepare(
            'SELECT *
             FROM events
             WHERE id = ?'
        );
        $statement->execute([$eventId]);
        $event = $statement->fetch();

        if (!$event) {
            http_response_code(404);
            echo '<h1>Event Not Found</h1>';
            exit;
        }

        $statement = $pdo->prepare(
            'SELECT *
             FROM event_questions
             WHERE event_id = ?
             ORDER BY sort_order, id'
        );
        $statement->execute([$eventId]);
        $questions = $statement->fetchAll();

        $conditionQuestions = array_filter(
            $questions,
            fn (array $question) =>
                in_array(
                    $question['question_type'],
                    ['select', 'checkbox'],
                    true
                )
        );

        require dirname(__DIR__, 3)
            . '/templates/admin/events/questions.php';
    }

    public function store(int $eventId): void
    {
        $questionText = trim((string) ($_POST['question_text'] ?? ''));
        $questionTextEs = trim((string) ($_POST['question_text_es'] ?? ''));
        $questionType = (string) ($_POST['question_type'] ?? 'text');
        $required = isset($_POST['required']) ? 1 : 0;
        $optionsRaw = trim((string) ($_POST['options'] ?? ''));
        $optionsRawEs = trim((string) ($_POST['options_es'] ?? ''));

        [$conditionalQuestionId, $conditionalOperator, $conditionalValue] =
            $this->conditionalSettings();

        [$blocksRegistration, $blockingOperator, $blockingValue,
            $blockingMessage, $blockingMessageEs] =
            $this->blockingSettings();

        $allowedTypes = ['text', 'textarea', 'select', 'checkbox'];

        if (
            $questionText === ''
            || !in_array($questionType, $allowedTypes, true)
        ) {
            $this->redirectError(
                $eventId,
                'Please enter a valid question.'
            );
        }

        [$optionsJson, $optionsJsonEs, $optionsError] =
            $this->buildOptions(
                $questionType,
                $optionsRaw,
                $optionsRawEs
            );

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
             FROM event_questions
             WHERE event_id = ?'
        );
        $statement->execute([$eventId]);
        $sortOrder = (int) $statement->fetchColumn();

        $statement = $pdo->prepare(
            'INSERT INTO event_questions
                (
                    event_id,
                    question_text,
                    question_text_es,
                    question_type,
                    options_json,
                    options_json_es,
                    required,
                    sort_order,
                    enabled,
                    conditional_question_id,
                    conditional_operator,
                    conditional_value,
                    blocks_registration,
                    blocking_operator,
                    blocking_value,
                    blocking_message,
                    blocking_message_es
                )
             VALUES
                (?, ?, ?, ?, ?, ?, ?, ?, 1, ?, ?, ?, ?, ?, ?, ?, ?)'
        );

        $statement->execute([
            $eventId,
            $questionText,
            $questionTextEs !== '' ? $questionTextEs : null,
            $questionType,
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

        header(
            'Location: /admin/events/'
            . $eventId
            . '/questions?created=1'
        );
        exit;
    }

    public function update(
        int $eventId,
        int $questionId
    ): void {
        $questionText = trim((string) ($_POST['question_text'] ?? ''));
        $questionTextEs = trim((string) ($_POST['question_text_es'] ?? ''));
        $questionType = (string) ($_POST['question_type'] ?? 'text');
        $required = isset($_POST['required']) ? 1 : 0;
        $enabled = isset($_POST['enabled']) ? 1 : 0;
        $sortOrder = max(0, (int) ($_POST['sort_order'] ?? 0));
        $optionsRaw = trim((string) ($_POST['options'] ?? ''));
        $optionsRawEs = trim((string) ($_POST['options_es'] ?? ''));

        [$conditionalQuestionId, $conditionalOperator, $conditionalValue] =
            $this->conditionalSettings();

        if (
            $conditionalQuestionId !== null
            && $conditionalQuestionId === $questionId
        ) {
            $conditionalQuestionId = null;
            $conditionalOperator = null;
            $conditionalValue = null;
        }

        [$blocksRegistration, $blockingOperator, $blockingValue,
            $blockingMessage, $blockingMessageEs] =
            $this->blockingSettings();

        $allowedTypes = ['text', 'textarea', 'select', 'checkbox'];

        if (
            $questionText === ''
            || !in_array($questionType, $allowedTypes, true)
        ) {
            $this->redirectError(
                $eventId,
                'Please enter a valid question.'
            );
        }

        [$optionsJson, $optionsJsonEs, $optionsError] =
            $this->buildOptions(
                $questionType,
                $optionsRaw,
                $optionsRawEs
            );

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
            'UPDATE event_questions
             SET
                question_text = ?,
                question_text_es = ?,
                question_type = ?,
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
             WHERE id = ?
               AND event_id = ?'
        );

        $statement->execute([
            $questionText,
            $questionTextEs !== '' ? $questionTextEs : null,
            $questionType,
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

        header(
            'Location: /admin/events/'
            . $eventId
            . '/questions?saved=1'
        );
        exit;
    }

    private function conditionalSettings(): array
    {
        $questionId =
            isset($_POST['conditional_question_id'])
            && $_POST['conditional_question_id'] !== ''
                ? (int) $_POST['conditional_question_id']
                : null;

        $operator = $_POST['conditional_operator'] ?? null;

        $value =
            isset($_POST['conditional_value'])
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

        $operator =
            (string) ($_POST['blocking_operator'] ?? 'equals');

        if (!in_array($operator, ['equals', 'not_equals'], true)) {
            $operator = 'equals';
        }

        $value = trim(
            (string) ($_POST['blocking_value'] ?? '')
        );

        $message = trim(
            (string) ($_POST['blocking_message'] ?? '')
        );

        $messageEs = trim(
            (string) ($_POST['blocking_message_es'] ?? '')
        );

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
                && !in_array(
                    $value,
                    array_map('strval', $options),
                    true
                )
            ) {
                return 'The Registration Qualification value must exactly match an English option.';
            }
        }

        return null;
    }

    private function redirectError(
        int $eventId,
        string $message
    ): never {
        header(
            'Location: /admin/events/'
            . $eventId
            . '/questions?error='
            . urlencode($message)
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
            return [
                null,
                null,
                'Dropdown questions need at least two English options.',
            ];
        }

        if (
            $questionType === 'checkbox'
            && $options === []
            && $optionsEs !== []
        ) {
            return [
                null,
                null,
                'Enter the English checkbox options before adding Spanish translations.',
            ];
        }

        if (
            $optionsEs !== []
            && count($optionsEs) !== count($options)
        ) {
            return [
                null,
                null,
                'Spanish options must have the same number of lines as the English options.',
            ];
        }

        return [
            $options !== []
                ? json_encode($options, JSON_UNESCAPED_UNICODE)
                : null,
            $optionsEs !== []
                ? json_encode($optionsEs, JSON_UNESCAPED_UNICODE)
                : null,
            null,
        ];
    }

    private function parseOptions(string $optionsRaw): array
    {
        return array_values(
            array_filter(
                array_map(
                    'trim',
                    preg_split('/\r\n|\r|\n/', $optionsRaw)
                ),
                fn ($value) => $value !== ''
            )
        );
    }
}
