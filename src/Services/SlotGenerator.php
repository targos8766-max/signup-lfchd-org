<?php

declare(strict_types=1);

namespace Boneblaze\SignupLfchdOrg\Services;

use DateInterval;
use DateTimeImmutable;
use PDO;
use RuntimeException;

class SlotGenerator
{
    public function __construct(
        private PDO $pdo
    ) {
    }

    public function generateForEvent(
        int $eventId,
        string $eventDate,
        string $startTime,
        string $endTime,
        int $intervalMinutes,
        int $defaultCapacity
    ): int {
        if ($intervalMinutes <= 0) {
            throw new RuntimeException('Interval must be greater than zero.');
        }

        if ($defaultCapacity < 1) {
            throw new RuntimeException('Default capacity must be at least 1.');
        }

        $start = new DateTimeImmutable(
            $eventDate . ' ' . $startTime
        );

        $end = new DateTimeImmutable(
            $eventDate . ' ' . $endTime
        );

        if ($end <= $start) {
            throw new RuntimeException(
                'Event end time must be later than the start time.'
            );
        }

        $interval = new DateInterval(
            'PT' . $intervalMinutes . 'M'
        );

        $insert = $this->pdo->prepare(
            'INSERT INTO event_slots
                (
                    event_id,
                    start_datetime,
                    end_datetime,
                    capacity,
                    enabled
                )
             VALUES
                (?, ?, ?, ?, 1)'
        );

        $slotCount = 0;
        $current = $start;

        while ($current < $end) {
            $slotEnd = $current->add($interval);

            if ($slotEnd > $end) {
                break;
            }

            $insert->execute([
                $eventId,
                $current->format('Y-m-d H:i:s'),
                $slotEnd->format('Y-m-d H:i:s'),
                $defaultCapacity,
            ]);

            $slotCount++;
            $current = $slotEnd;
        }

        return $slotCount;
    }
}