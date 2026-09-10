<?php

declare(strict_types=1);

namespace Boneblaze\SignupLfchdOrg\Controllers\Admin;

use Boneblaze\SignupLfchdOrg\Database\Database;
use Boneblaze\SignupLfchdOrg\Models\Event;
use Boneblaze\SignupLfchdOrg\Services\SlotGenerator;
use Boneblaze\SignupLfchdOrg\Services\EntraAuth;
use Throwable;

class EventController
{
    public function edit(int $eventId): void
    {
        $pdo = Database::connection();

        $eventModel = new Event($pdo);
        $event = $eventModel->find($eventId);

        if (!$event) {
            http_response_code(404);
            echo 'Event not found.';
            return;
        }

        require dirname(__DIR__, 3)
            . '/templates/admin/events/edit.php';
    }

    public function open(int $eventId): void
    {
        $pdo = Database::connection();

        $statement = $pdo->prepare(
            'SELECT COUNT(*)
            FROM event_slots
            WHERE event_id = ?
            AND enabled = 1'
        );

        $statement->execute([$eventId]);

        $enabledSlots = (int) $statement->fetchColumn();

        if ($enabledSlots === 0) {
            header(
                'Location: /admin?error='
                . urlencode(
                    'This event cannot be opened because it has no enabled time slots.'
                )
            );
            exit;
        }

        $statement = $pdo->prepare(
            'UPDATE events
            SET status = ?
            WHERE id = ?'
        );

        $statement->execute([
            'open',
            $eventId,
        ]);

        header('Location: /admin?opened=1');
        exit;
    }

    public function close(int $eventId): void
    {
        $pdo = Database::connection();

        $statement = $pdo->prepare(
            'UPDATE events
            SET status = ?
            WHERE id = ?'
        );

        $statement->execute([
            'closed',
            $eventId,
        ]);

        header('Location: /admin?closed=1');
        exit;
    }

    public function update(int $eventId): void
    {
        $pdo = Database::connection();

        $eventModel = new Event($pdo);
        $event = $eventModel->find($eventId);

        if (!$event) {
            http_response_code(404);
            echo 'Event not found.';
            return;
        }

        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $location = trim($_POST['location'] ?? '');
        $eventDate = $_POST['event_date'] ?? '';
        $startTime = $_POST['start_time'] ?? '';
        $endTime = $_POST['end_time'] ?? '';
        $intervalMinutes = (int) ($_POST['interval_minutes'] ?? 0);
        $defaultCapacity = (int) ($_POST['default_capacity'] ?? 0);
        $status = $_POST['status'] ?? 'draft';

        $signupOpenAt = trim($_POST['signup_open_at'] ?? '');
        $signupCloseAt = trim($_POST['signup_close_at'] ?? '');

        $signupOpenAt = $signupOpenAt !== ''
            ? str_replace('T', ' ', $signupOpenAt) . ':00'
            : null;

        $signupCloseAt = $signupCloseAt !== ''
            ? str_replace('T', ' ', $signupCloseAt) . ':00'
            : null;

        $errors = [];

        if ($title === '') {
            $errors[] = 'Event name is required.';
        }

        if ($eventDate === '') {
            $errors[] = 'Event date is required.';
        }

        if ($startTime === '') {
            $errors[] = 'Start time is required.';
        }

        if ($endTime === '') {
            $errors[] = 'End time is required.';
        }

        if ($intervalMinutes < 1) {
            $errors[] = 'Interval must be at least 1 minute.';
        }

        if ($defaultCapacity < 1) {
            $errors[] = 'Default seats must be at least 1.';
        }

        if ($startTime !== '' && $endTime !== '' && $endTime <= $startTime) {
            $errors[] = 'End time must be later than start time.';
        }

        $allowedStatuses = [
            'draft',
            'open',
            'closed',
            'cancelled',
        ];

        if (!in_array($status, $allowedStatuses, true)) {
            $errors[] = 'Invalid event status.';
        }

        if (
            $signupOpenAt !== null
            && $signupCloseAt !== null
            && $signupCloseAt <= $signupOpenAt
        ) {
            $errors[] = 'Signup close time must be later than signup open time.';
        }

        if ($errors !== []) {
            $event = array_merge($event, [
                'title' => $title,
                'description' => $description,
                'location' => $location,
                'event_date' => $eventDate,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'interval_minutes' => $intervalMinutes,
                'default_capacity' => $defaultCapacity,
                'status' => $status,
                'signup_open_at' => $signupOpenAt,
                'signup_close_at' => $signupCloseAt,
            ]);

            require dirname(__DIR__, 3)
                . '/templates/admin/events/edit.php';

            return;
        }

        $eventModel->update($eventId, [
            'title' => $title,
            'description' => $description !== '' ? $description : null,
            'location' => $location !== '' ? $location : null,
            'event_date' => $eventDate,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'interval_minutes' => $intervalMinutes,
            'default_capacity' => $defaultCapacity,
            'status' => $status,
            'signup_open_at' => $signupOpenAt,
            'signup_close_at' => $signupCloseAt,
        ]);

        header(
            'Location: /admin/events/' . $eventId . '/edit?saved=1'
        );

        exit;
    }

    public function create(): void
    {
        require dirname(__DIR__, 3) . '/templates/admin/events/create.php';
    }

    public function duplicate(int $eventId): void
    {
        $pdo = Database::connection();

        $pdo->beginTransaction();

        try {
            $eventModel = new Event($pdo);

            $event = $eventModel->find($eventId);

            if (!$event) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }

                http_response_code(404);
                echo '<h1>Event Not Found</h1>';
                exit;
            }

            $newEventId = $eventModel->create([
                'title' => $event['title'] . ' - Copy',
                'public_slug' => bin2hex(random_bytes(12)),
                'description' => $event['description'],
                'location' => $event['location'],
                'event_date' => $event['event_date'],
                'start_time' => $event['start_time'],
                'end_time' => $event['end_time'],
                'interval_minutes' => $event['interval_minutes'],
                'default_capacity' => $event['default_capacity'],
                'status' => 'draft',
                'signup_open_at' => $event['signup_open_at'],
                'signup_close_at' => $event['signup_close_at'],
                'created_by' =>
                    EntraAuth::user()['email']
                    ?? 'unknown',
            ]);

            /*
             * Copy time slots.
             */
            $statement = $pdo->prepare(
                'SELECT
                    start_datetime,
                    end_datetime,
                    capacity,
                    enabled
                FROM event_slots
                WHERE event_id = ?
                ORDER BY start_datetime'
            );

            $statement->execute([$eventId]);

            $slots = $statement->fetchAll();

            $insertSlot = $pdo->prepare(
                'INSERT INTO event_slots
                    (
                        event_id,
                        start_datetime,
                        end_datetime,
                        capacity,
                        enabled
                    )
                VALUES
                    (?, ?, ?, ?, ?)'
            );

            foreach ($slots as $slot) {
                $insertSlot->execute([
                    $newEventId,
                    $slot['start_datetime'],
                    $slot['end_datetime'],
                    $slot['capacity'],
                    $slot['enabled'],
                ]);
            }

            /*
             * Load all custom questions for the source event.
             *
             * We copy the questions first without their conditional
             * relationship. This lets every new question receive its
             * own ID before we rebuild the dependencies.
             */
            $statement = $pdo->prepare(
                'SELECT
                    id,
                    question_text,
                    question_type,
                    options_json,
                    required,
                    sort_order,
                    enabled,
                    conditional_question_id,
                    conditional_operator,
                    conditional_value
                FROM event_questions
                WHERE event_id = ?
                ORDER BY sort_order, id'
            );

            $statement->execute([$eventId]);

            $questions = $statement->fetchAll();

            $insertQuestion = $pdo->prepare(
                'INSERT INTO event_questions
                    (
                        event_id,
                        question_text,
                        question_type,
                        options_json,
                        required,
                        sort_order,
                        enabled,
                        conditional_question_id,
                        conditional_operator,
                        conditional_value
                    )
                VALUES
                    (?, ?, ?, ?, ?, ?, ?, NULL, NULL, NULL)'
            );

            /*
             * Map:
             * original question ID => copied question ID
             */
            $questionIdMap = [];

            foreach ($questions as $question) {
                $insertQuestion->execute([
                    $newEventId,
                    $question['question_text'],
                    $question['question_type'],
                    $question['options_json'],
                    $question['required'],
                    $question['sort_order'],
                    $question['enabled'],
                ]);

                $questionIdMap[
                    (int) $question['id']
                ] = (int) $pdo->lastInsertId();
            }

            /*
             * Second pass:
             * Rebuild conditional relationships using the copied IDs.
             *
             * Example:
             * Original Question 12 depends on Original Question 10.
             * The copied Question 12 must depend on copied Question 10,
             * not on the original event's Question 10.
             */
            $updateCondition = $pdo->prepare(
                'UPDATE event_questions
                SET
                    conditional_question_id = ?,
                    conditional_operator = ?,
                    conditional_value = ?
                WHERE id = ?
                AND event_id = ?'
            );

            foreach ($questions as $question) {
                $originalConditionQuestionId =
                    $question['conditional_question_id'];

                if ($originalConditionQuestionId === null) {
                    continue;
                }

                $originalConditionQuestionId =
                    (int) $originalConditionQuestionId;

                $newConditionQuestionId =
                    $questionIdMap[$originalConditionQuestionId]
                    ?? null;

                /*
                 * If the controlling question was not part of this
                 * event for some unexpected reason, do not create a
                 * cross-event dependency.
                 */
                if ($newConditionQuestionId === null) {
                    continue;
                }

                $newQuestionId =
                    $questionIdMap[
                        (int) $question['id']
                    ];

                $updateCondition->execute([
                    $newConditionQuestionId,
                    $question['conditional_operator'],
                    $question['conditional_value'],
                    $newQuestionId,
                    $newEventId,
                ]);
            }

            $pdo->commit();

            header(
                'Location: /admin/events/'
                . $newEventId
                . '/edit?duplicated=1'
            );
            exit;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            throw $e;
        }
    }

    public function delete(int $eventId): void
    {
        $pdo = Database::connection();

        $statement = $pdo->prepare(
            'SELECT
                e.id,
                e.status,
                (
                    SELECT COUNT(*)
                    FROM registrations r
                    WHERE r.event_id = e.id
                ) AS registration_count
            FROM events e
            WHERE e.id = ?'
        );

        $statement->execute([$eventId]);

        $event = $statement->fetch();

        if (!$event) {
            http_response_code(404);
            echo '<h1>Event Not Found</h1>';
            exit;
        }

        if ($event['status'] !== 'draft') {
            header(
                'Location: /admin?error='
                . urlencode(
                    'Only draft events can be deleted.'
                )
            );
            exit;
        }

        if ((int) $event['registration_count'] > 0) {
            header(
                'Location: /admin?error='
                . urlencode(
                    'This event cannot be deleted because registrations exist.'
                )
            );
            exit;
        }

        $delete = $pdo->prepare(
            'DELETE FROM events WHERE id = ?'
        );

        $delete->execute([$eventId]);

        header('Location: /admin?deleted=1');
        exit;
    }

    public function store(): void
    {
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $location = trim($_POST['location'] ?? '');
        $eventDate = $_POST['event_date'] ?? '';
        $startTime = $_POST['start_time'] ?? '';
        $endTime = $_POST['end_time'] ?? '';
        $intervalMinutes = (int) ($_POST['interval_minutes'] ?? 0);
        $defaultCapacity = (int) ($_POST['default_capacity'] ?? 0);

        $errors = [];

        if ($title === '') {
            $errors[] = 'Event name is required.';
        }

        if ($eventDate === '') {
            $errors[] = 'Event date is required.';
        }

        if ($startTime === '') {
            $errors[] = 'Start time is required.';
        }

        if ($endTime === '') {
            $errors[] = 'End time is required.';
        }

        if ($intervalMinutes < 1) {
            $errors[] = 'Interval must be at least 1 minute.';
        }

        if ($defaultCapacity < 1) {
            $errors[] = 'Default seats must be at least 1.';
        }

        if ($startTime !== '' && $endTime !== '' && $endTime <= $startTime) {
            $errors[] = 'End time must be later than start time.';
        }

        if ($errors !== []) {
            require dirname(__DIR__, 3) . '/templates/admin/events/create.php';
            return;
        }

        $pdo = Database::connection();

        try {
            $pdo->beginTransaction();

            $eventModel = new Event($pdo);

            $slug = bin2hex(random_bytes(12));

            $eventId = $eventModel->create([
                'title' => $title,
                'public_slug' => $slug,
                'description' => $description !== '' ? $description : null,
                'location' => $location !== '' ? $location : null,
                'event_date' => $eventDate,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'interval_minutes' => $intervalMinutes,
                'default_capacity' => $defaultCapacity,
                'status' => 'draft',
                'created_by' => EntraAuth::user()['email'] ?? 'unknown',
            ]);

            $slotGenerator = new SlotGenerator($pdo);

            $slotGenerator->generateForEvent(
                $eventId,
                $eventDate,
                $startTime,
                $endTime,
                $intervalMinutes,
                $defaultCapacity
            );

            $pdo->commit();

            header(
                'Location: /admin/events/' . $eventId . '/slots'
            );

            exit;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $errors = [
                'Unable to create the event: ' . $e->getMessage(),
            ];

            require dirname(__DIR__, 3) . '/templates/admin/events/create.php';
        }
    }
}
