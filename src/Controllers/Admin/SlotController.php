<?php

declare(strict_types=1);

namespace Boneblaze\SignupLfchdOrg\Controllers\Admin;

use Boneblaze\SignupLfchdOrg\Database\Database;

class SlotController
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
            'SELECT *
             FROM event_slots
             WHERE event_id = ?
             ORDER BY start_datetime'
        );

        $slotStatement->execute([$eventId]);
        $slots = $slotStatement->fetchAll();

        require dirname(__DIR__, 3)
            . '/templates/admin/events/slots.php';
    }

    public function update(int $eventId): void
    {
        $pdo = Database::connection();

        $capacities = $_POST['capacity'] ?? [];
        $enabledSlots = $_POST['enabled'] ?? [];

        $statement = $pdo->prepare(
            'UPDATE event_slots
             SET capacity = ?, enabled = ?
             WHERE id = ? AND event_id = ?'
        );

        foreach ($capacities as $slotId => $capacity) {
            $slotId = (int) $slotId;
            $capacity = max(1, (int) $capacity);

            $enabled = isset($enabledSlots[$slotId]) ? 1 : 0;

            $statement->execute([
                $capacity,
                $enabled,
                $slotId,
                $eventId,
            ]);
        }

        header(
            'Location: /admin/events/' . $eventId . '/slots?saved=1'
        );

        exit;
    }
}