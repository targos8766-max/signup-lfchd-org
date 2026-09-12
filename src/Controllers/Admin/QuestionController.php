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
        $questionText = trim(
            (string) ($_POST['question_text'] ?? '')
        );

        $questionTextEs = trim(
            (string) ($_POST['question_text_es'] ?? '')
        );

        $questionType =
            (string) ($_POST['question_type'] ?? 'text');

        $required =
            isset($_POST['required']) ? 1 : 0;

        $optionsRaw = trim(
            (string) ($_POST['options'] ?? '')
        );

        $optionsRawEs = trim(
            (string) ($_POST['options_es'] ?? '')
        );

        $conditionalQuestionId =
            isset($_POST['conditional_question_id'])
            && $_POST['conditional_question_id'] !== ''
                ? (int) $_POST['conditional_question_id']
                : null;

        $conditionalOperator =
            $_POST['conditional_operator'] ?? null;

        $conditionalValue =
            isset($_POST['conditional_value'])
                ? trim((string) $_POST['conditional_value'])
                : null;

        if (
            $conditionalOperator !== null
            && $conditionalOperator !== ''
            && !in_array(
                $conditionalOperator,
                ['equals', 'not_equals'],
                true
            )
        ) {
            $conditionalOperator = null;
        }

        if ($conditionalQuestionId === null) {
            $conditionalOperator = null;
            $conditionalValue = null;
        }

        if ($conditionalValue === '') {
            $conditionalValue = null;
        }

        $allowedTypes = [
            'text',
            'textarea',
            'select',
            'checkbox',
        ];

        if (
            $questionText === ''
            || !in_array(
                $questionType,
                $allowedTypes,
                true
            )
        ) {
            header(
                'Location: /admin/events/'
                . $eventId
                . '/questions?error='
                . urlencode('Please enter a valid question.')
            );

            exit;
        }

        [$optionsJson, $optionsJsonEs, $optionsError] =
            $this->buildOptions(
                $questionType,
                $optionsRaw,
                $optionsRawEs
            );

        if ($optionsError !== null) {
            header(
                'Location: /admin/events/'
                . $eventId
                . '/questions?error='
                . urlencode($optionsError)
            );

            exit;
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
                    conditional_value
                )
             VALUES
                (?, ?, ?, ?, ?, ?, ?, ?, 1, ?, ?, ?)'
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
        $questionText = trim(
            (string) ($_POST['question_text'] ?? '')
        );

        $questionTextEs = trim(
            (string) ($_POST['question_text_es'] ?? '')
        );

        $questionType =
            (string) ($_POST['question_type'] ?? 'text');

        $required =
            isset($_POST['required']) ? 1 : 0;

        $enabled =
            isset($_POST['enabled']) ? 1 : 0;

        $sortOrder = max(
            0,
            (int) ($_POST['sort_order'] ?? 0)
        );

        $optionsRaw = trim(
            (string) ($_POST['options'] ?? '')
        );

        $optionsRawEs = trim(
            (string) ($_POST['options_es'] ?? '')
        );

        $conditionalQuestionId =
            isset($_POST['conditional_question_id'])
            && $_POST['conditional_question_id'] !== ''
                ? (int) $_POST['conditional_question_id']
                : null;

        $conditionalOperator =
            $_POST['conditional_operator'] ?? null;

        $conditionalValue =
            isset($_POST['conditional_value'])
                ? trim((string) $_POST['conditional_value'])
                : null;

        if (
            $conditionalOperator !== null
            && $conditionalOperator !== ''
            && !in_array(
                $conditionalOperator,
                ['equals', 'not_equals'],
                true
            )
        ) {
            $conditionalOperator = null;
        }

        if ($conditionalQuestionId === null) {
            $conditionalOperator = null;
            $conditionalValue = null;
        }

        if ($conditionalValue === '') {
            $conditionalValue = null;
        }

        if (
            $conditionalQuestionId !== null
            && $conditionalQuestionId === $questionId
        ) {
            $conditionalQuestionId = null;
            $conditionalOperator = null;
            $conditionalValue = null;
        }

        $allowedTypes = [
            'text',
            'textarea',
            'select',
            'checkbox',
        ];

        if (
            $questionText === ''
            || !in_array(
                $questionType,
                $allowedTypes,
                true
            )
        ) {
            header(
                'Location: /admin/events/'
                . $eventId
                . '/questions?error='
                . urlencode('Please enter a valid question.')
            );

            exit;
        }

        [$optionsJson, $optionsJsonEs, $optionsError] =
            $this->buildOptions(
                $questionType,
                $optionsRaw,
                $optionsRawEs
            );

        if ($optionsError !== null) {
            header(
                'Location: /admin/events/'
                . $eventId
                . '/questions?error='
                . urlencode($optionsError)
            );

            exit;
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
                conditional_value = ?
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

        /*
         * Checkbox questions may remain a single yes/no checkbox when
         * no options are entered. If options are entered, they become
         * a multi-option checkbox question.
         */
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
                ? json_encode(
                    $options,
                    JSON_UNESCAPED_UNICODE
                )
                : null,
            $optionsEs !== []
                ? json_encode(
                    $optionsEs,
                    JSON_UNESCAPED_UNICODE
                )
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
