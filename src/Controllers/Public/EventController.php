<?php

declare(strict_types=1);

namespace Boneblaze\SignupLfchdOrg\Controllers\Public;

use Boneblaze\SignupLfchdOrg\Database\Database;
use Boneblaze\SignupLfchdOrg\Services\MailgunMailer;
use Boneblaze\SignupLfchdOrg\Services\TwilioSms;
use DateTimeImmutable;
use Throwable;

class EventController
{
    public function show(string $slug): void
    {
        $pdo = Database::connection();

        $statement = $pdo->prepare(
            'SELECT *
             FROM events
             WHERE public_slug = ?
             LIMIT 1'
        );

        $statement->execute([$slug]);
        $event = $statement->fetch();

        if (!$event) {
            http_response_code(404);
            echo 'Event not found.';
            return;
        }

        $now = new DateTimeImmutable();

        $registrationOpen = true;
        $registrationMessage = null;

        if ($event['status'] !== 'open') {
            $registrationOpen = false;

            $registrationMessage = match ($event['status']) {
                'draft' => 'Registration is not yet open.',
                'closed' => 'Registration for this event is closed.',
                'cancelled' => 'This event has been cancelled.',
                default => 'Registration is unavailable.',
            };
        }

        if (
            $registrationOpen
            && !empty($event['signup_open_at'])
            && $now < new DateTimeImmutable($event['signup_open_at'])
        ) {
            $registrationOpen = false;
            $registrationMessage = 'Registration has not opened yet.';
        }

        if (
            $registrationOpen
            && !empty($event['signup_close_at'])
            && $now >= new DateTimeImmutable($event['signup_close_at'])
        ) {
            $registrationOpen = false;
            $registrationMessage = 'Registration is closed.';
        }

        $slotStatement = $pdo->prepare(
            'SELECT
                es.*,

                (
                    SELECT COUNT(*)
                    FROM registrations r
                    WHERE r.slot_id = es.id
                      AND r.status = "confirmed"
                ) AS registered_count

             FROM event_slots es
             WHERE es.event_id = ?
               AND es.enabled = 1
             ORDER BY es.start_datetime'
        );

        $slotStatement->execute([$event['id']]);
        $slots = $slotStatement->fetchAll();

        foreach ($slots as &$slot) {
            $slot['remaining'] = max(
                0,
                (int) $slot['capacity']
                - (int) $slot['registered_count']
            );
        }

        unset($slot);

        $questions = $this->getQuestions(
            (int) $event['id']
        );

        require dirname(__DIR__, 3)
            . '/templates/public/event.php';
    }

    private function getQuestions(int $eventId): array
    {
        $pdo = Database::connection();

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

    private function questionIsActive(
        array $question,
        array $submittedAnswers
    ): bool {
        $conditionalQuestionId =
            $question['conditional_question_id'] ?? null;

        if (
            $conditionalQuestionId === null
            || $conditionalQuestionId === ''
        ) {
            return true;
        }

        $conditionalQuestionId =
            (int) $conditionalQuestionId;

        $operator =
            $question['conditional_operator'] ?? null;

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

        $actualValue =
            (string) $actualValue;

        if ($operator === 'equals') {
            return $actualValue === $expectedValue;
        }

        if ($operator === 'not_equals') {
            return $actualValue !== $expectedValue;
        }

        return true;
    }

    public function register(string $slug): void
    {
        $pdo = Database::connection();

        $statement = $pdo->prepare(
            'SELECT *
            FROM events
            WHERE public_slug = ?
            LIMIT 1'
        );

        $statement->execute([$slug]);
        $event = $statement->fetch();

        if (!$event) {
            http_response_code(404);
            echo 'Event not found.';
            return;
        }

        $slotId = (int) ($_POST['slot_id'] ?? 0);

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

        $smsOptIn =
            isset($_POST['sms_opt_in'])
            && (string) $_POST['sms_opt_in'] === '1';

        $smsPhone = null;

        $department = trim(
            (string) ($_POST['department'] ?? '')
        );

        $questions = $this->getQuestions(
            (int) $event['id']
        );

        $submittedAnswers =
            $_POST['answers'] ?? [];

        if (!is_array($submittedAnswers)) {
            $submittedAnswers = [];
        }

        $errors = [];

        if ($slotId < 1) {
            $errors[] = 'Please select a time.';
        }

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

        if ($smsOptIn) {
            if ($phone === '') {
                $errors[] =
                    'A phone number is required if you choose SMS notifications.';
            } else {
                $smsPhone =
                    $this->normalizeSmsPhone($phone);

                if ($smsPhone === null) {
                    $errors[] =
                        'Please enter a valid U.S. phone number for SMS notifications.';
                }
            }
        }

        $now = new DateTimeImmutable();

        if ($event['status'] !== 'open') {
            $errors[] =
                'Registration is not currently open.';
        }

        if (
            !empty($event['signup_open_at'])
            && $now
                < new DateTimeImmutable(
                    $event['signup_open_at']
                )
        ) {
            $errors[] =
                'Registration has not opened yet.';
        }

        if (
            !empty($event['signup_close_at'])
            && $now
                >= new DateTimeImmutable(
                    $event['signup_close_at']
                )
        ) {
            $errors[] =
                'Registration is closed.';
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

            $questionId =
                (int) $question['id'];

            $answer =
                $submittedAnswers[$questionId]
                ?? '';

            if (is_array($answer)) {
                $answer = '';
            }

            $answer = trim(
                (string) $answer
            );

            if (
                $question['question_type']
                    === 'checkbox'
                && (int) $question['required'] === 1
                && $answer !== '1'
            ) {
                $errors[] =
                    $question['question_text']
                    . ' is required.';

                continue;
            }

            if (
                $question['question_type']
                    !== 'checkbox'
                && (int) $question['required'] === 1
                && $answer === ''
            ) {
                $errors[] =
                    $question['question_text']
                    . ' is required.';

                continue;
            }

            if (
                $question['question_type']
                    === 'select'
                && $answer !== ''
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
                        'Please select a valid option for '
                        . $question['question_text']
                        . '.';
                }
            }
        }

        if ($errors !== []) {
            $this->showWithErrors(
                $event,
                $errors,
                $_POST
            );

            return;
        }

        try {
            $pdo->beginTransaction();

            $slotStatement = $pdo->prepare(
                'SELECT *
                 FROM event_slots
                 WHERE id = ?
                   AND event_id = ?
                 FOR UPDATE'
            );

            $slotStatement->execute([
                $slotId,
                $event['id'],
            ]);

            $slot = $slotStatement->fetch();

            if (!$slot || !(int) $slot['enabled']) {
                throw new \RuntimeException(
                    'The selected time is no longer available.'
                );
            }

            $countStatement = $pdo->prepare(
                'SELECT COUNT(*)
                 FROM registrations
                 WHERE slot_id = ?
                   AND status = "confirmed"'
            );

            $countStatement->execute([$slotId]);

            $registered = (int) $countStatement->fetchColumn();
            $capacity = (int) $slot['capacity'];

            if ($registered >= $capacity) {
                throw new \RuntimeException(
                    'The selected time is full. Please choose another time.'
                );
            }

            $confirmationCode = bin2hex(random_bytes(16));

            $insert = $pdo->prepare(
                'INSERT INTO registrations
                    (
                        event_id,
                        slot_id,
                        first_name,
                        last_name,
                        email,
                        phone,
                        sms_opt_in,
                        sms_opt_in_at,
                        department,
                        confirmation_code,
                        status
                    )
                 VALUES
                    (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, "confirmed")'
            );

            $insert->execute([
                $event['id'],
                $slotId,
                $firstName,
                $lastName,
                $email !== '' ? $email : null,
                $phone !== '' ? $phone : null,
                $smsOptIn ? 1 : 0,
                $smsOptIn
                    ? (new DateTimeImmutable())->format('Y-m-d H:i:s')
                    : null,
                $department !== '' ? $department : null,
                $confirmationCode,
            ]);

            $registrationId =
                (int) $pdo->lastInsertId();

            $answerInsert = $pdo->prepare(
                'INSERT INTO registration_answers
                    (
                        registration_id,
                        question_id,
                        answer_text
                    )
                VALUES
                    (?, ?, ?)'
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

                $questionId =
                    (int) $question['id'];

                $answer =
                    $submittedAnswers[$questionId]
                    ?? '';

                if (is_array($answer)) {
                    $answer = '';
                }

                $answer = trim((string) $answer);

                if (
                    $question['question_type']
                    === 'checkbox'
                ) {
                    $answer = $answer === '1'
                        ? '1'
                        : '0';
                }

                if (
                    $answer === ''
                    && $question['question_type']
                        !== 'checkbox'
                ) {
                    continue;
                }

                $answerInsert->execute([
                    $registrationId,
                    $questionId,
                    $answer,
                ]);
            }

            /*
             * Commit before contacting Mailgun or Twilio.
             * Delivery failures must never undo a valid signup.
             */
            $pdo->commit();

            $registrationForNotifications = [
                'id' => $registrationId,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $email,
                'phone' => $smsOptIn
                    ? $smsPhone
                    : ($phone !== '' ? $phone : null),
                'sms_opt_in' => $smsOptIn ? 1 : 0,
                'department' => $department !== ''
                    ? $department
                    : null,
                'confirmation_code' =>
                    $confirmationCode,
                'status' => 'confirmed',
            ];

            if ($email !== '') {
                try {
                    $mailer =
                        new MailgunMailer();

                    $mailer->sendRegistrationConfirmation(
                        $event,
                        $slot,
                        $registrationForNotifications
                    );
                } catch (Throwable $mailException) {
                    error_log(
                        'Mailgun confirmation email failed for registration '
                        . $registrationId
                        . ': '
                        . $mailException->getMessage()
                    );
                }
            }

            if ($smsOptIn && $smsPhone !== null) {
                try {
                    $sms =
                        new TwilioSms();

                    $sms->sendRegistrationConfirmation(
                        $event,
                        $slot,
                        $registrationForNotifications
                    );
                } catch (Throwable $smsException) {
                    error_log(
                        'Twilio confirmation SMS failed for registration '
                        . $registrationId
                        . ': '
                        . $smsException->getMessage()
                    );
                }
            }

            header(
                'Location: /event/'
                . rawurlencode($slug)
                . '/confirmation/'
                . rawurlencode($confirmationCode)
            );

            exit;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $this->showWithErrors(
                $event,
                [$e->getMessage()],
                $_POST
            );
        }
    }

    public function cancel(
        string $slug,
        string $confirmationCode
    ): void {
        $pdo = Database::connection();

        try {
            $pdo->beginTransaction();

            $statement = $pdo->prepare(
                'SELECT
                    r.*,
                    e.title,
                    e.location,
                    e.public_slug,
                    es.start_datetime,
                    es.end_datetime
                 FROM registrations r
                 INNER JOIN events e
                    ON e.id = r.event_id
                 INNER JOIN event_slots es
                    ON es.id = r.slot_id
                 WHERE e.public_slug = ?
                   AND r.confirmation_code = ?
                 LIMIT 1
                 FOR UPDATE'
            );

            $statement->execute([
                $slug,
                $confirmationCode,
            ]);

            $registration = $statement->fetch();

            if (!$registration) {
                $pdo->rollBack();

                http_response_code(404);
                echo 'Registration not found.';
                return;
            }

            if ($registration['status'] === 'cancelled') {
                $pdo->commit();

                header(
                    'Location: /event/'
                    . rawurlencode($slug)
                    . '/confirmation/'
                    . rawurlencode($confirmationCode)
                );

                exit;
            }

            if ($registration['status'] !== 'confirmed') {
                $pdo->rollBack();

                http_response_code(400);
                echo 'This registration cannot be cancelled.';
                return;
            }

            $update = $pdo->prepare(
                'UPDATE registrations
                 SET status = "cancelled"
                 WHERE id = ?
                   AND status = "confirmed"'
            );

            $update->execute([
                $registration['id'],
            ]);

            $pdo->commit();

            if (!empty($registration['email'])) {
                try {
                    $mailer =
                        new MailgunMailer();

                    $mailer->sendCancellationConfirmation(
                        $registration
                    );
                } catch (Throwable $mailException) {
                    error_log(
                        'Mailgun cancellation email failed for registration '
                        . (int) $registration['id']
                        . ': '
                        . $mailException->getMessage()
                    );
                }
            }

            header(
                'Location: /event/'
                . rawurlencode($slug)
                . '/confirmation/'
                . rawurlencode($confirmationCode)
                . '?cancelled=1'
            );

            exit;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            throw $e;
        }
    }

    public function confirmation(
        string $slug,
        string $confirmationCode
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
             WHERE e.public_slug = ?
               AND r.confirmation_code = ?
             LIMIT 1'
        );

        $statement->execute([
            $slug,
            $confirmationCode,
        ]);

        $registration = $statement->fetch();

        if (!$registration) {
            http_response_code(404);
            echo 'Registration not found.';
            return;
        }

        require dirname(__DIR__, 3)
            . '/templates/public/confirmation.php';
    }

    private function showWithErrors(
        array $event,
        array $errors,
        array $old
    ): void {
        $pdo = Database::connection();

        $registrationOpen = true;
        $registrationMessage = null;

        $slotStatement = $pdo->prepare(
            'SELECT
                es.*,

                (
                    SELECT COUNT(*)
                    FROM registrations r
                    WHERE r.slot_id = es.id
                      AND r.status = "confirmed"
                ) AS registered_count

             FROM event_slots es
             WHERE es.event_id = ?
               AND es.enabled = 1
             ORDER BY es.start_datetime'
        );

        $slotStatement->execute([$event['id']]);
        $slots = $slotStatement->fetchAll();

        foreach ($slots as &$slot) {
            $slot['remaining'] = max(
                0,
                (int) $slot['capacity']
                - (int) $slot['registered_count']
            );
        }

        unset($slot);

        $questions = $this->getQuestions(
            (int) $event['id']
        );

        require dirname(__DIR__, 3)
            . '/templates/public/event.php';
    }

    private function normalizeSmsPhone(
        string $phone
    ): ?string {
        $phone = trim($phone);

        if ($phone === '') {
            return null;
        }

        if (str_starts_with($phone, '+')) {
            $digits = preg_replace(
                '/\D+/',
                '',
                substr($phone, 1)
            );

            if (
                is_string($digits)
                && strlen($digits) >= 10
                && strlen($digits) <= 15
            ) {
                return '+' . $digits;
            }

            return null;
        }

        $digits = preg_replace(
            '/\D+/',
            '',
            $phone
        );

        if (!is_string($digits)) {
            return null;
        }

        if (strlen($digits) === 10) {
            return '+1' . $digits;
        }

        if (
            strlen($digits) === 11
            && str_starts_with($digits, '1')
        ) {
            return '+' . $digits;
        }

        return null;
    }
}
