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

        $language =
            (($_GET['lang'] ?? 'en') === 'es')
                ? 'es'
                : 'en';

        [$registrationOpen, $registrationMessage] =
            $this->registrationStatus(
                $event,
                $language
            );

        $slots = $this->getSlots(
            (int) $event['id']
        );

        $questions = $this->getQuestions(
            (int) $event['id']
        );

        $old = [
            'preferred_language' => $language,
        ];

        $registrationStatus = [
            'open' => $registrationOpen,
            'message' => $registrationMessage,
        ];

        require dirname(__DIR__, 3)
            . '/templates/public/event.php';
    }

    private function getQuestions(int $eventId): array
    {
        $pdo = Database::connection();

        $statement = $pdo->prepare(
            'SELECT
                q.*,
                s.title AS section_title,
                s.title_es AS section_title_es,
                s.description AS section_description,
                s.description_es AS section_description_es,
                s.sort_order AS section_sort_order
             FROM event_questions q
             LEFT JOIN event_question_sections s
                ON s.id = q.section_id
                AND s.event_id = q.event_id
             WHERE q.event_id = ?
               AND q.enabled = 1
               AND (
                    q.section_id IS NULL
                    OR s.enabled = 1
               )
             ORDER BY
                CASE WHEN q.section_id IS NULL THEN 0 ELSE 1 END,
                COALESCE(s.sort_order, 0),
                q.sort_order,
                q.id'
        );

        $statement->execute([$eventId]);

        return $statement->fetchAll();
    }

    private function getSlots(int $eventId): array
    {
        $pdo = Database::connection();

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

        $slotStatement->execute([$eventId]);

        $slots = $slotStatement->fetchAll();

        foreach ($slots as &$slot) {
            $slot['available'] = max(
                0,
                (int) $slot['capacity']
                - (int) $slot['registered_count']
            );
        }

        unset($slot);

        return $slots;
    }

    private function registrationStatus(
        array $event,
        string $language
    ): array {
        $now = new DateTimeImmutable();

        $messages = $language === 'es'
            ? [
                'draft' => 'El registro aún no está abierto.',
                'closed' => 'El registro para este evento está cerrado.',
                'cancelled' => 'Este evento ha sido cancelado.',
                'unavailable' => 'El registro no está disponible.',
                'not_open' => 'El registro aún no ha comenzado.',
                'closed_now' => 'El registro está cerrado.',
            ]
            : [
                'draft' => 'Registration is not yet open.',
                'closed' => 'Registration for this event is closed.',
                'cancelled' => 'This event has been cancelled.',
                'unavailable' => 'Registration is unavailable.',
                'not_open' => 'Registration has not opened yet.',
                'closed_now' => 'Registration is closed.',
            ];

        $registrationOpen = true;
        $registrationMessage = null;

        if ($event['status'] !== 'open') {
            $registrationOpen = false;

            $registrationMessage = match ($event['status']) {
                'draft' => $messages['draft'],
                'closed' => $messages['closed'],
                'cancelled' => $messages['cancelled'],
                default => $messages['unavailable'],
            };
        }

        if (
            $registrationOpen
            && !empty($event['signup_open_at'])
            && $now < new DateTimeImmutable($event['signup_open_at'])
        ) {
            $registrationOpen = false;
            $registrationMessage = $messages['not_open'];
        }

        if (
            $registrationOpen
            && !empty($event['signup_close_at'])
            && $now >= new DateTimeImmutable($event['signup_close_at'])
        ) {
            $registrationOpen = false;
            $registrationMessage = $messages['closed_now'];
        }

        return [
            $registrationOpen,
            $registrationMessage,
        ];
    }

    private function localizedQuestionText(
        array $question,
        string $language
    ): string {
        if (
            $language === 'es'
            && !empty($question['question_text_es'])
        ) {
            return $question['question_text_es'];
        }

        return $question['question_text'];
    }

    private function canonicalOptions(
        array $question
    ): array {
        $options = json_decode(
            $question['options_json'] ?? '[]',
            true
        );

        if (!is_array($options)) {
            return [];
        }

        return array_values(
            array_map('strval', $options)
        );
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
            $actualValues = array_map(
                'strval',
                $actualValue
            );

            $contains = in_array(
                $expectedValue,
                $actualValues,
                true
            );

            if ($operator === 'equals') {
                return $contains;
            }

            if ($operator === 'not_equals') {
                return !$contains;
            }

            return true;
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

    private function questionBlocksRegistration(
        array $question,
        mixed $answer
    ): bool {
        if ((int) ($question['blocks_registration'] ?? 0) !== 1) {
            return false;
        }

        $operator =
            (string) ($question['blocking_operator'] ?? '');

        $expectedValue =
            (string) ($question['blocking_value'] ?? '');

        if (
            !in_array($operator, ['equals', 'not_equals'], true)
            || $expectedValue === ''
        ) {
            return false;
        }

        if (is_array($answer)) {
            $actualValues = array_map('strval', $answer);

            $matches = in_array(
                $expectedValue,
                $actualValues,
                true
            );
        } else {
            $actualValue = trim((string) $answer);

            /*
             * An unanswered non-checkbox question should not trigger a
             * qualification rule. This prevents "not equals" from blocking
             * someone before they have actually answered the question.
             */
            if (
                $actualValue === ''
                && $question['question_type'] !== 'checkbox'
            ) {
                return false;
            }

            if (
                $question['question_type'] === 'checkbox'
                && $actualValue !== '1'
            ) {
                $actualValue = '0';
            }

            $matches = $actualValue === $expectedValue;
        }

        return $operator === 'equals'
            ? $matches
            : !$matches;
    }

    private function blockingMessage(
        array $question,
        string $language
    ): string {
        if ($language === 'es') {
            $spanish = trim(
                (string) ($question['blocking_message_es'] ?? '')
            );

            if ($spanish !== '') {
                return $spanish;
            }
        }

        return trim(
            (string) ($question['blocking_message'] ?? '')
        );
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

        $preferredLanguage =
            (($_POST['preferred_language'] ?? 'en') === 'es')
                ? 'es'
                : 'en';

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

        $questions = $this->getQuestions(
            (int) $event['id']
        );

        $submittedAnswers =
            $_POST['answers'] ?? [];

        if (!is_array($submittedAnswers)) {
            $submittedAnswers = [];
        }

        $errors = [];

        $messages = $preferredLanguage === 'es'
            ? [
                'select_time' => 'Seleccione una hora.',
                'first_name' => 'El nombre es obligatorio.',
                'last_name' => 'El apellido es obligatorio.',
                'email' => 'Ingrese una dirección de correo electrónico válida.',
                'phone_required' => 'Se requiere un número de teléfono si elige recibir notificaciones por SMS.',
                'phone_invalid' => 'Ingrese un número de teléfono válido de EE. UU. para recibir notificaciones por SMS.',
                'not_open' => 'El registro no está abierto actualmente.',
                'not_started' => 'El registro aún no ha comenzado.',
                'closed' => 'El registro está cerrado.',
                'required_suffix' => ' es obligatorio.',
                'invalid_option_prefix' => 'Seleccione una opción válida para ',
                'invalid_email_question' => 'Ingrese una dirección de correo electrónico válida para ',
                'invalid_phone_question' => 'Ingrese un número de teléfono válido de EE. UU. para ',
                'invalid_date_question' => 'Ingrese una fecha válida para ',
                'future_birthdate_question' => 'La fecha de nacimiento no puede ser futura para ',
                'invalid_number_question' => 'Ingrese un número válido para ',
                'minimum_age_prefix' => 'La edad mínima para ',
                'maximum_age_prefix' => 'La edad máxima para ',
                'years_suffix' => ' años.',
                'slot_unavailable' => 'La hora seleccionada ya no está disponible.',
                'slot_full' => 'La hora seleccionada está llena. Seleccione otra hora.',
            ]
            : [
                'select_time' => 'Please select a time.',
                'first_name' => 'First name is required.',
                'last_name' => 'Last name is required.',
                'email' => 'Please enter a valid email address.',
                'phone_required' => 'A phone number is required if you choose SMS notifications.',
                'phone_invalid' => 'Please enter a valid U.S. phone number for SMS notifications.',
                'not_open' => 'Registration is not currently open.',
                'not_started' => 'Registration has not opened yet.',
                'closed' => 'Registration is closed.',
                'required_suffix' => ' is required.',
                'invalid_option_prefix' => 'Please select a valid option for ',
                'invalid_email_question' => 'Please enter a valid email address for ',
                'invalid_phone_question' => 'Please enter a valid U.S. phone number for ',
                'invalid_date_question' => 'Please enter a valid date for ',
                'future_birthdate_question' => 'Birthdate cannot be in the future for ',
                'invalid_number_question' => 'Please enter a valid number for ',
                'minimum_age_prefix' => 'Minimum age for ',
                'maximum_age_prefix' => 'Maximum age for ',
                'years_suffix' => ' years.',
                'slot_unavailable' => 'The selected time is no longer available.',
                'slot_full' => 'The selected time is full. Please choose another time.',
            ];

        if ($slotId < 1) {
            $errors[] = $messages['select_time'];
        }

        if ($firstName === '') {
            $errors[] = $messages['first_name'];
        }

        if ($lastName === '') {
            $errors[] = $messages['last_name'];
        }

        if (
            $email !== ''
            && !filter_var(
                $email,
                FILTER_VALIDATE_EMAIL
            )
        ) {
            $errors[] = $messages['email'];
        }

        if ($smsOptIn) {
            if ($phone === '') {
                $errors[] =
                    $messages['phone_required'];
            } else {
                $smsPhone =
                    $this->normalizeSmsPhone($phone);

                if ($smsPhone === null) {
                    $errors[] =
                        $messages['phone_invalid'];
                }
            }
        }

        $now = new DateTimeImmutable();

        if ($event['status'] !== 'open') {
            $errors[] =
                $messages['not_open'];
        }

        if (
            !empty($event['signup_open_at'])
            && $now
                < new DateTimeImmutable(
                    $event['signup_open_at']
                )
        ) {
            $errors[] =
                $messages['not_started'];
        }

        if (
            !empty($event['signup_close_at'])
            && $now
                >= new DateTimeImmutable(
                    $event['signup_close_at']
                )
        ) {
            $errors[] =
                $messages['closed'];
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

            $questionText =
                $this->localizedQuestionText(
                    $question,
                    $preferredLanguage
                );

            $options =
                $this->canonicalOptions($question);

            $isMultiCheckbox =
                $question['question_type'] === 'checkbox'
                && $options !== [];

            if ($isMultiCheckbox) {
                $answers = is_array($answer)
                    ? array_values(
                        array_map('strval', $answer)
                    )
                    : [];

                $answers = array_values(
                    array_intersect(
                        $answers,
                        $options
                    )
                );

                if (
                    (int) $question['required'] === 1
                    && $answers === []
                ) {
                    $errors[] =
                        $questionText
                        . $messages['required_suffix'];
                }

                if (
                    is_array($answer)
                    && count($answers) !== count($answer)
                ) {
                    $errors[] =
                        $messages['invalid_option_prefix']
                        . $questionText
                        . '.';
                }

                continue;
            }

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
                    $questionText
                    . $messages['required_suffix'];
                continue;
            }

            if (
                $question['question_type']
                    !== 'checkbox'
                && (int) $question['required'] === 1
                && $answer === ''
            ) {
                $errors[] =
                    $questionText
                    . $messages['required_suffix'];
                continue;
            }

            if (
                $question['question_type']
                    === 'select'
                && $answer !== ''
                && !in_array(
                    $answer,
                    $options,
                    true
                )
            ) {
                $errors[] =
                    $messages['invalid_option_prefix']
                    . $questionText
                    . '.';
            }
            if (
                $answer !== ''
                && in_array($question['question_type'], ['text', 'textarea'], true)
            ) {
                $dataType = (string) ($question['data_type'] ?? 'text');

                if ($dataType === 'email' && !filter_var($answer, FILTER_VALIDATE_EMAIL)) {
                    $errors[] = $messages['invalid_email_question'] . $questionText . '.';
                }

                if ($dataType === 'phone' && $this->normalizeSmsPhone($answer) === null) {
                    $errors[] = $messages['invalid_phone_question'] . $questionText . '.';
                }

                if (in_array($dataType, ['date', 'birthdate'], true)) {
                    $date = $this->strictDate($answer);

                    if ($date === null) {
                        $errors[] = $messages['invalid_date_question'] . $questionText . '.';
                    } elseif ($dataType === 'birthdate' && $date > new DateTimeImmutable('today')) {
                        $errors[] = $messages['future_birthdate_question'] . $questionText . '.';
                    } elseif ($dataType === 'birthdate') {
                        $validation = json_decode((string) ($question['validation_json'] ?? ''), true);
                        $validation = is_array($validation) ? $validation : [];
                        $age = $date->diff(new DateTimeImmutable('today'))->y;

                        if (isset($validation['min_age']) && $age < (int) $validation['min_age']) {
                            $errors[] = $messages['minimum_age_prefix'] . $questionText . ': '
                                . (int) $validation['min_age'] . $messages['years_suffix'];
                        }

                        if (isset($validation['max_age']) && $age > (int) $validation['max_age']) {
                            $errors[] = $messages['maximum_age_prefix'] . $questionText . ': '
                                . (int) $validation['max_age'] . $messages['years_suffix'];
                        }
                    }
                }

                if ($dataType === 'number' && !is_numeric($answer)) {
                    $errors[] = $messages['invalid_number_question'] . $questionText . '.';
                }
            }

        }

        /*
         * Enforce registration qualification rules on the server.
         * Only active/visible questions participate, so answers from hidden
         * conditional questions cannot disqualify a registrant.
         */
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

            if (
                $this->questionBlocksRegistration(
                    $question,
                    $answer
                )
            ) {
                $message =
                    $this->blockingMessage(
                        $question,
                        $preferredLanguage
                    );

                if ($message !== '') {
                    $errors[] = $message;
                }
            }
        }

        if ($errors !== []) {
            $this->showWithErrors(
                $event,
                $errors,
                $_POST,
                $preferredLanguage
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
                    $messages['slot_unavailable']
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
                    $messages['slot_full']
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
                        preferred_language,
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
                $preferredLanguage,
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

                $options =
                    $this->canonicalOptions($question);

                $isMultiCheckbox =
                    $question['question_type'] === 'checkbox'
                    && $options !== [];

                if ($isMultiCheckbox) {
                    $answers = is_array($answer)
                        ? array_values(
                            array_intersect(
                                array_map('strval', $answer),
                                $options
                            )
                        )
                        : [];

                    if ($answers === []) {
                        continue;
                    }

                    $answerInsert->execute([
                        $registrationId,
                        $questionId,
                        json_encode(
                            $answers,
                            JSON_UNESCAPED_UNICODE
                        ),
                    ]);

                    continue;
                }

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
                'preferred_language' =>
                    $preferredLanguage,
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
                $_POST,
                $preferredLanguage
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
                    e.title_es,
                    e.location,
                    e.location_es,
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

                echo $registration['preferred_language'] === 'es'
                    ? 'Este registro no se puede cancelar.'
                    : 'This registration cannot be cancelled.';

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
                e.title_es,
                e.description,
                e.description_es,
                e.location,
                e.location_es,
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
        array $old,
        string $language
    ): void {
        [$registrationOpen, $registrationMessage] =
            $this->registrationStatus(
                $event,
                $language
            );

        $slots = $this->getSlots(
            (int) $event['id']
        );

        $questions = $this->getQuestions(
            (int) $event['id']
        );

        $old['preferred_language'] = $language;

        $registrationStatus = [
            'open' => $registrationOpen,
            'message' => $registrationMessage,
        ];

        require dirname(__DIR__, 3)
            . '/templates/public/event.php';
    }

    private function strictDate(string $value): ?DateTimeImmutable
    {
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);

        if (!$date || $date->format('Y-m-d') !== $value) {
            return null;
        }

        return $date;
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
