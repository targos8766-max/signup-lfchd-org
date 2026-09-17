<?php

declare(strict_types=1);

namespace Boneblaze\SignupLfchdOrg\Models;

use PDO;

class Event
{
    public function __construct(
        private PDO $pdo
    ) {
    }

    public function create(array $data): int
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO events
                (
                    title, title_es, public_slug, description, description_es,
                    location, location_es, event_date, start_time, end_time,
                    interval_minutes, default_capacity, status, admin_only,
                    signup_open_at, signup_close_at, created_by
                )
             VALUES
                (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );

        $statement->execute([
            $data['title'],
            $data['title_es'] ?? null,
            $data['public_slug'],
            $data['description'] ?? null,
            $data['description_es'] ?? null,
            $data['location'] ?? null,
            $data['location_es'] ?? null,
            $data['event_date'],
            $data['start_time'],
            $data['end_time'],
            $data['interval_minutes'],
            $data['default_capacity'],
            $data['status'] ?? 'draft',
            (int) ($data['admin_only'] ?? 0),
            $data['signup_open_at'] ?? null,
            $data['signup_close_at'] ?? null,
            $data['created_by'] ?? null,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function find(int $eventId): array|false
    {
        $statement = $this->pdo->prepare(
            'SELECT * FROM events WHERE id = ?'
        );
        $statement->execute([$eventId]);
        return $statement->fetch();
    }

    public function update(int $eventId, array $data): void
    {
        $statement = $this->pdo->prepare(
            'UPDATE events
             SET title = ?, title_es = ?, description = ?, description_es = ?,
                 location = ?, location_es = ?, event_date = ?, start_time = ?,
                 end_time = ?, interval_minutes = ?, default_capacity = ?,
                 status = ?, admin_only = ?, signup_open_at = ?,
                 signup_close_at = ?
             WHERE id = ?'
        );

        $statement->execute([
            $data['title'],
            $data['title_es'] ?? null,
            $data['description'] ?? null,
            $data['description_es'] ?? null,
            $data['location'] ?? null,
            $data['location_es'] ?? null,
            $data['event_date'],
            $data['start_time'],
            $data['end_time'],
            $data['interval_minutes'],
            $data['default_capacity'],
            $data['status'],
            (int) ($data['admin_only'] ?? 0),
            $data['signup_open_at'] ?? null,
            $data['signup_close_at'] ?? null,
            $eventId,
        ]);
    }
}
