<?php

declare(strict_types=1);

namespace Boneblaze\SignupLfchdOrg\Controllers\Admin;

use Boneblaze\SignupLfchdOrg\Database\Database;
use Boneblaze\SignupLfchdOrg\Services\EntraAuth;
use Boneblaze\SignupLfchdOrg\Services\MailgunMailer;
use Throwable;

class RegistrationController
{
    public function index(int $eventId): void
    {
        $pdo = Database::connection();

        $eventStatement = $pdo->prepare(
            'SELECT * FROM events WHERE id = ?'
        );

        $eventStatement->execute([$eventId]);
        $event = $eventStatement->fetch();

        if (!$event) {
            http_response_code(404);
            echo 'Event not found.';
            return;
        }

        $slotStatement = $pdo->prepare(
            'SELECT
                es.*,
                (
                    SELECT COUNT(*)
                    FROM registrations r
                    WHERE r.slot_id = es.id
                      AND r.status = "confirmed"
                ) AS registration_count
             FROM event_slots es
             WHERE es.event_id = ?
             ORDER BY es.start_datetime'
        );

        $slotStatement->execute([$eventId]);
        $slots = $slotStatement->fetchAll();

        $questionStatement = $pdo->prepare(
            'SELECT
                id,
                question_text,
                question_type,
                sort_order,
                enabled
             FROM event_questions
             WHERE event_id = ?
             ORDER BY sort_order, id'
        );

        $questionStatement->execute([$eventId]);
        $questions = $questionStatement->fetchAll();

        $registrationStatement = $pdo->prepare(
            'SELECT
                r.*,
                es.start_datetime,
                es.end_datetime
             FROM registrations r
             INNER JOIN event_slots es
                ON es.id = r.slot_id
             WHERE r.event_id = ?
             ORDER BY
                es.start_datetime,
                r.last_name,
                r.first_name'
        );

        $registrationStatement->execute([$eventId]);
        $registrations = $registrationStatement->fetchAll();

        $answerStatement = $pdo->prepare(
            'SELECT
                ra.registration_id,
                ra.question_id,
                ra.answer_text
             FROM registration_answers ra
             INNER JOIN registrations r
                ON r.id = ra.registration_id
             WHERE r.event_id = ?'
        );

        $answerStatement->execute([$eventId]);
        $answerRows = $answerStatement->fetchAll();

        $answersByRegistration = [];

        foreach ($answerRows as $answerRow) {
            $registrationId =
                (int) $answerRow['registration_id'];

            $questionId =
                (int) $answerRow['question_id'];

            if (!isset(
                $answersByRegistration[$registrationId]
            )) {
                $answersByRegistration[$registrationId] = [];
            }

            $answersByRegistration[$registrationId][$questionId] =
                $answerRow['answer_text'];
        }

        foreach ($registrations as &$registration) {
            $registrationId = (int) $registration['id'];

            $registration['answers'] =
                $answersByRegistration[$registrationId]
                ?? [];
        }

        unset($registration);

        $registrationsBySlot = [];

        foreach ($registrations as $registration) {
            $slotId = (int) $registration['slot_id'];

            if (!isset($registrationsBySlot[$slotId])) {
                $registrationsBySlot[$slotId] = [];
            }

            $registrationsBySlot[$slotId][] = $registration;
        }

        $totalConfirmed = 0;
        $totalCancelled = 0;

        foreach ($registrations as $registration) {
            if ($registration['status'] === 'confirmed') {
                $totalConfirmed++;
            }

            if ($registration['status'] === 'cancelled') {
                $totalCancelled++;
            }
        }

        require dirname(__DIR__, 3)
            . '/templates/admin/events/registrations.php';
    }

    public function resendConfirmation(
        int $eventId,
        int $registrationId
    ): void {
        $pdo = Database::connection();

        $statement = $pdo->prepare(
            'SELECT
                r.*,
                e.title,
                e.description,
                e.location,
                e.event_date,
                e.public_slug,
                es.start_datetime,
                es.end_datetime
             FROM registrations r
             INNER JOIN events e
                ON e.id = r.event_id
             INNER JOIN event_slots es
                ON es.id = r.slot_id
             WHERE r.id = ?
               AND r.event_id = ?
             LIMIT 1'
        );

        $statement->execute([
            $registrationId,
            $eventId,
        ]);

        $registration = $statement->fetch();

        if (!$registration) {
            header(
                'Location: /admin/events/'
                . $eventId
                . '/registrations?error='
                . rawurlencode('Registration not found.')
            );

            exit;
        }

        $email = trim(
            (string) ($registration['email'] ?? '')
        );

        if ($email === '') {
            header(
                'Location: /admin/events/'
                . $eventId
                . '/registrations?error='
                . rawurlencode(
                    'This registration does not have an email address.'
                )
            );

            exit;
        }

        try {
            $mailer = new MailgunMailer();

            $mailer->sendRegistrationConfirmation(
                [
                    'title' => $registration['title'],
                    'description' => $registration['description'],
                    'location' => $registration['location'],
                    'event_date' => $registration['event_date'],
                    'public_slug' => $registration['public_slug'],
                ],
                [
                    'start_datetime' => $registration['start_datetime'],
                    'end_datetime' => $registration['end_datetime'],
                ],
                [
                    'id' => $registration['id'],
                    'first_name' => $registration['first_name'],
                    'last_name' => $registration['last_name'],
                    'email' => $registration['email'],
                    'phone' => $registration['phone'],
                    'confirmation_code' =>
                        $registration['confirmation_code'],
                    'status' => $registration['status'],
                ]
            );

            header(
                'Location: /admin/events/'
                . $eventId
                . '/registrations?resent=1'
            );

            exit;
        } catch (Throwable $e) {
            error_log(
                'Mailgun resend failed for registration '
                . $registrationId
                . ': '
                . $e->getMessage()
            );

            header(
                'Location: /admin/events/'
                . $eventId
                . '/registrations?error='
                . rawurlencode(
                    'Unable to resend the confirmation email. Please check the mail configuration or server logs.'
                )
            );

            exit;
        }
    }

    public function exportCsv(int $eventId): void
    {
        $pdo = Database::connection();

        $eventStatement = $pdo->prepare(
            'SELECT * FROM events WHERE id = ?'
        );

        $eventStatement->execute([$eventId]);
        $event = $eventStatement->fetch();

        if (!$event) {
            http_response_code(404);
            echo 'Event not found.';
            return;
        }

        $questionStatement = $pdo->prepare(
            'SELECT
                id,
                question_text,
                question_type
             FROM event_questions
             WHERE event_id = ?
             ORDER BY sort_order, id'
        );

        $questionStatement->execute([$eventId]);
        $questions = $questionStatement->fetchAll();

        $statement = $pdo->prepare(
            'SELECT
                r.id,
                r.first_name,
                r.last_name,
                r.email,
                r.phone,
                r.status,
                r.confirmation_code,
                r.created_at,
                es.start_datetime,
                es.end_datetime
             FROM registrations r
             INNER JOIN event_slots es
                ON es.id = r.slot_id
             WHERE r.event_id = ?
             ORDER BY
                es.start_datetime,
                r.last_name,
                r.first_name'
        );

        $statement->execute([$eventId]);
        $registrations = $statement->fetchAll();

        $answerStatement = $pdo->prepare(
            'SELECT
                ra.registration_id,
                ra.question_id,
                ra.answer_text
             FROM registration_answers ra
             INNER JOIN registrations r
                ON r.id = ra.registration_id
             WHERE r.event_id = ?'
        );

        $answerStatement->execute([$eventId]);
        $answerRows = $answerStatement->fetchAll();

        $answersByRegistration = [];

        foreach ($answerRows as $answerRow) {
            $registrationId =
                (int) $answerRow['registration_id'];

            $questionId =
                (int) $answerRow['question_id'];

            if (!isset(
                $answersByRegistration[$registrationId]
            )) {
                $answersByRegistration[$registrationId] = [];
            }

            $answersByRegistration[$registrationId][$questionId] =
                $answerRow['answer_text'];
        }

        $safeTitle = preg_replace(
            '/[^A-Za-z0-9_-]+/',
            '-',
            strtolower($event['title'])
        );

        $safeTitle = trim($safeTitle ?: 'event', '-');

        $filename =
            $safeTitle
            . '-registrations-'
            . date('Y-m-d')
            . '.csv';

        header('Content-Type: text/csv; charset=utf-8');
        header(
            'Content-Disposition: attachment; filename="'
            . $filename
            . '"'
        );

        $output = fopen('php://output', 'w');

        if ($output === false) {
            http_response_code(500);
            echo 'Unable to create export.';
            return;
        }

        fwrite($output, "\xEF\xBB\xBF");

        $headers = [
            'First Name',
            'Last Name',
            'Email',
            'Phone',
            'Date',
            'Start Time',
            'End Time',
            'Status',
            'Confirmation Code',
            'Registered At',
        ];

        foreach ($questions as $question) {
            $headers[] = $question['question_text'];
        }

        fputcsv($output, $headers);

        foreach ($registrations as $registration) {
            $start = new \DateTimeImmutable(
                $registration['start_datetime']
            );

            $end = new \DateTimeImmutable(
                $registration['end_datetime']
            );

            $row = [
                $registration['first_name'],
                $registration['last_name'],
                $registration['email'],
                $registration['phone'],
                $start->format('m/d/Y'),
                $start->format('g:i A'),
                $end->format('g:i A'),
                ucfirst($registration['status']),
                $registration['confirmation_code'],
                $registration['created_at'],
            ];

            $registrationId = (int) $registration['id'];
            $answers =
                $answersByRegistration[$registrationId]
                ?? [];

            foreach ($questions as $question) {
                $questionId = (int) $question['id'];

                $answer = $answers[$questionId] ?? '';

                if ($question['question_type'] === 'checkbox') {
                    if ((string) $answer === '1') {
                        $answer = 'Yes';
                    } elseif ((string) $answer === '0') {
                        $answer = 'No';
                    }
                }

                $row[] = $answer;
            }

            fputcsv($output, $row);
        }

        fclose($output);
        exit;
    }

    public function edit(
        int $eventId,
        int $registrationId
    ): void {
        $pdo = Database::connection();

        $statement = $pdo->prepare(
            'SELECT
                r.*,
                e.title AS event_title,
                es.start_datetime,
                es.end_datetime
            FROM registrations r
            INNER JOIN events e
                ON e.id = r.event_id
            INNER JOIN event_slots es
                ON es.id = r.slot_id
            WHERE r.id = ?
            AND r.event_id = ?
            LIMIT 1'
        );

        $statement->execute([
            $registrationId,
            $eventId,
        ]);

        $registration = $statement->fetch();

        if (!$registration) {
            http_response_code(404);
            echo 'Registration not found.';
            return;
        }

        $questions = $this->getQuestions(
            $pdo,
            $eventId
        );

        $answers = $this->getRegistrationAnswers(
            $pdo,
            $registrationId
        );

        require dirname(__DIR__, 3)
            . '/templates/admin/events/registration-edit.php';
    }

    public function update(
        int $eventId,
        int $registrationId
    ): void {
        $pdo = Database::connection();

        $statement = $pdo->prepare(
            'SELECT *
            FROM registrations
            WHERE id = ?
            AND event_id = ?
            LIMIT 1'
        );

        $statement->execute([
            $registrationId,
            $eventId,
        ]);

        $registration = $statement->fetch();

        if (!$registration) {
            http_response_code(404);
            echo 'Registration not found.';
            return;
        }

        $firstName = trim(
            (string) ($_POST['first_name'] ?? '')
        );

        $lastName = trim(
            (string) ($_POST['last_name'] ?? '')
        );

        $email = trim(
            (string) ($_POST['email'] ?? '')
        );

        $phone = trim(
            (string) ($_POST['phone'] ?? '')
        );

        $submittedAnswers = $_POST['answers'] ?? [];

        if (!is_array($submittedAnswers)) {
            $submittedAnswers = [];
        }

        $questions = $this->getQuestions(
            $pdo,
            $eventId
        );

        $errors = [];

        if ($firstName === '') {
            $errors[] = 'First name is required.';
        }

        if ($lastName === '') {
            $errors[] = 'Last name is required.';
        }

        if (
            $email !== ''
            && !filter_var(
                $email,
                FILTER_VALIDATE_EMAIL
            )
        ) {
            $errors[] =
                'Please enter a valid email address.';
        }

        foreach ($questions as $question) {
            if (
                !$this->questionIsActive(
                    $question,
                    $submittedAnswers
                )
            ) {
                continue;
            }

            $questionId = (int) $question['id'];

            $answer =
                $submittedAnswers[$questionId]
                ?? '';

            if (is_array($answer)) {
                $answer = '';
            }

            $answer = (string) $answer;

            if (
                (int) $question['required'] === 1
                && $question['question_type']
                    === 'checkbox'
                && $answer !== '1'
            ) {
                $errors[] =
                    $question['question_text']
                    . ' is required.';
            }

            if (
                (int) $question['required'] === 1
                && $question['question_type']
                    !== 'checkbox'
                && trim($answer) === ''
            ) {
                $errors[] =
                    $question['question_text']
                    . ' is required.';
            }

            if (
                $question['question_type']
                    === 'select'
                && trim($answer) !== ''
            ) {
                $options = json_decode(
                    $question['options_json']
                        ?? '[]',
                    true
                );

                if (
                    !is_array($options)
                    || !in_array(
                        $answer,
                        $options,
                        true
                    )
                ) {
                    $errors[] =
                        'Please select a valid answer for '
                        . $question['question_text']
                        . '.';
                }
            }
        }

        if ($errors !== []) {
            $registration = array_merge(
                $registration,
                [
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'email' => $email,
                    'phone' => $phone,
                ]
            );

            $eventStatement = $pdo->prepare(
                'SELECT title
                FROM events
                WHERE id = ?'
            );

            $eventStatement->execute([$eventId]);

            $registration['event_title'] =
                $eventStatement->fetchColumn();

            $slotStatement = $pdo->prepare(
                'SELECT start_datetime, end_datetime
                FROM event_slots
                WHERE id = ?'
            );

            $slotStatement->execute([
                $registration['slot_id']
            ]);

            $slot = $slotStatement->fetch();

            $registration['start_datetime'] =
                $slot['start_datetime'];

            $registration['end_datetime'] =
                $slot['end_datetime'];

            $answers = $submittedAnswers;

            require dirname(__DIR__, 3)
                . '/templates/admin/events/registration-edit.php';

            return;
        }

        try {
            $pdo->beginTransaction();

            $update = $pdo->prepare(
                'UPDATE registrations
                SET
                    first_name = ?,
                    last_name = ?,
                    email = ?,
                    phone = ?
                WHERE id = ?
                AND event_id = ?'
            );

            $update->execute([
                $firstName,
                $lastName,
                $email !== '' ? $email : null,
                $phone !== '' ? $phone : null,
                $registrationId,
                $eventId,
            ]);

            $deleteAnswers = $pdo->prepare(
                'DELETE FROM registration_answers
                 WHERE registration_id = ?'
            );

            $deleteAnswers->execute([
                $registrationId
            ]);

            $insertAnswer = $pdo->prepare(
                'INSERT INTO registration_answers
                    (
                        registration_id,
                        question_id,
                        answer_text
                    )
                 VALUES (?, ?, ?)'
            );

            foreach ($questions as $question) {
                if (
                    !$this->questionIsActive(
                        $question,
                        $submittedAnswers
                    )
                ) {
                    continue;
                }

                $questionId = (int) $question['id'];

                $answer =
                    $submittedAnswers[$questionId]
                    ?? '';

                if (is_array($answer)) {
                    $answer = '';
                }

                $answer = (string) $answer;

                if (
                    $question['question_type']
                    === 'checkbox'
                ) {
                    $answer =
                        $answer === '1'
                            ? '1'
                            : '0';
                } else {
                    $answer = trim($answer);
                }

                if (
                    $question['question_type']
                    !== 'checkbox'
                    && $answer === ''
                ) {
                    continue;
                }

                $insertAnswer->execute([
                    $registrationId,
                    $questionId,
                    $answer,
                ]);
            }

            $pdo->commit();

            header(
                'Location: /admin/events/'
                . $eventId
                . '/registrations?saved=1'
            );

            exit;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            throw $e;
        }
    }

    public function cancel(
        int $eventId,
        int $registrationId
    ): void {
        $pdo = Database::connection();

        $statement = $pdo->prepare(
            'UPDATE registrations
            SET status = "cancelled"
            WHERE id = ?
            AND event_id = ?
            AND status = "confirmed"'
        );

        $statement->execute([
            $registrationId,
            $eventId,
        ]);

        header(
            'Location: /admin/events/'
            . $eventId
            . '/registrations?cancelled=1'
        );

        exit;
    }

    public function restore(
        int $eventId,
        int $registrationId
    ): void {
        $pdo = Database::connection();

        try {
            $pdo->beginTransaction();

            $statement = $pdo->prepare(
                'SELECT *
                FROM registrations
                WHERE id = ?
                AND event_id = ?
                FOR UPDATE'
            );

            $statement->execute([
                $registrationId,
                $eventId,
            ]);

            $registration = $statement->fetch();

            if (!$registration) {
                throw new \RuntimeException(
                    'Registration not found.'
                );
            }

            $slotStatement = $pdo->prepare(
                'SELECT *
                FROM event_slots
                WHERE id = ?
                AND event_id = ?
                FOR UPDATE'
            );

            $slotStatement->execute([
                $registration['slot_id'],
                $eventId,
            ]);

            $slot = $slotStatement->fetch();

            if (!$slot) {
                throw new \RuntimeException(
                    'Time slot not found.'
                );
            }

            $countStatement = $pdo->prepare(
                'SELECT COUNT(*)
                FROM registrations
                WHERE slot_id = ?
                AND status = "confirmed"'
            );

            $countStatement->execute([
                $registration['slot_id']
            ]);

            $confirmedCount =
                (int) $countStatement->fetchColumn();

            if (
                $confirmedCount
                >= (int) $slot['capacity']
            ) {
                throw new \RuntimeException(
                    'This time slot is already full.'
                );
            }

            $update = $pdo->prepare(
                'UPDATE registrations
                SET status = "confirmed"
                WHERE id = ?
                AND event_id = ?'
            );

            $update->execute([
                $registrationId,
                $eventId,
            ]);

            $pdo->commit();

            header(
                'Location: /admin/events/'
                . $eventId
                . '/registrations?restored=1'
            );

            exit;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            header(
                'Location: /admin/events/'
                . $eventId
                . '/registrations?error='
                . rawurlencode($e->getMessage())
            );

            exit;
        }
    }

    public function delete(
        int $eventId,
        int $registrationId
    ): void {
        /*
        * Defense in depth: the route also requires an Administrator,
        * but deletion must be protected at the controller level too.
        */
        if (!EntraAuth::isAdministrator()) {
            http_response_code(403);
            echo 'Administrator access is required.';
            return;
        }

        $pdo = Database::connection();

        try {
            $pdo->beginTransaction();

            /*
            * Lock the registration while we verify that it exists
            * and is already cancelled.
            */
            $statement = $pdo->prepare(
                'SELECT id, status
                FROM registrations
                WHERE id = ?
                AND event_id = ?
                FOR UPDATE'
            );

            $statement->execute([
                $registrationId,
                $eventId,
            ]);

            $registration = $statement->fetch();

            if (!$registration) {
                throw new \RuntimeException(
                    'Registration not found.'
                );
            }

            if ($registration['status'] !== 'cancelled') {
                throw new \RuntimeException(
                    'Only cancelled registrations can be deleted.'
                );
            }

            /*
            * Keep the cancelled-status requirement in the DELETE itself
            * as an additional safeguard.
            *
            * registration_answers and registration_reminder_log are
            * automatically removed by their ON DELETE CASCADE foreign
            * keys.
            */
            $delete = $pdo->prepare(
                'DELETE FROM registrations
                WHERE id = ?
                AND event_id = ?
                AND status = "cancelled"'
            );

            $delete->execute([
                $registrationId,
                $eventId,
            ]);

            if ($delete->rowCount() !== 1) {
                throw new \RuntimeException(
                    'The registration could not be deleted.'
                );
            }

            $pdo->commit();

            header(
                'Location: /admin/events/'
                . $eventId
                . '/registrations?deleted=1'
            );

            exit;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            header(
                'Location: /admin/events/'
                . $eventId
                . '/registrations?error='
                . rawurlencode($e->getMessage())
            );

            exit;
        }
    }

    private function getQuestions(
        \PDO $pdo,
        int $eventId
    ): array {
        $statement = $pdo->prepare(
            'SELECT *
             FROM event_questions
             WHERE event_id = ?
               AND enabled = 1
             ORDER BY sort_order, id'
        );

        $statement->execute([$eventId]);

        return $statement->fetchAll();
    }

    private function getRegistrationAnswers(
        \PDO $pdo,
        int $registrationId
    ): array {
        $statement = $pdo->prepare(
            'SELECT
                question_id,
                answer_text
             FROM registration_answers
             WHERE registration_id = ?'
        );

        $statement->execute([
            $registrationId
        ]);

        $answers = [];

        foreach ($statement->fetchAll() as $row) {
            $answers[(int) $row['question_id']] =
                $row['answer_text'];
        }

        return $answers;
    }

    private function questionIsActive(
        array $question,
        array $submittedAnswers
    ): bool {
        $conditionalQuestionId =
            $question['conditional_question_id']
            ?? null;

        if (
            $conditionalQuestionId === null
            || $conditionalQuestionId === ''
        ) {
            return true;
        }

        $conditionalQuestionId =
            (int) $conditionalQuestionId;

        $operator =
            $question['conditional_operator']
            ?? null;

        $expectedValue =
            (string) (
                $question['conditional_value']
                ?? ''
            );

        $actualValue =
            $submittedAnswers[$conditionalQuestionId]
            ?? '';

        if (is_array($actualValue)) {
            $actualValue = '';
        }

        $actualValue = (string) $actualValue;

        if ($operator === 'equals') {
            return $actualValue === $expectedValue;
        }

        if ($operator === 'not_equals') {
            return $actualValue !== $expectedValue;
        }

        return true;
    }
}
