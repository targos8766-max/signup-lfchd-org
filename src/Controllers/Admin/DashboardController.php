<?php

declare(strict_types=1);

namespace Boneblaze\SignupLfchdOrg\Controllers\Admin;

use Boneblaze\SignupLfchdOrg\Database\Database;
use Boneblaze\SignupLfchdOrg\Services\EntraAuth;

class DashboardController
{
    public function index(): void
    {
        $pdo = Database::connection();

        $sql = 'SELECT
                    e.*,
                    (
                        SELECT COUNT(*)
                        FROM event_slots es
                        WHERE es.event_id = e.id
                    ) AS slot_count,
                    (
                        SELECT COALESCE(SUM(es.capacity), 0)
                        FROM event_slots es
                        WHERE es.event_id = e.id
                          AND es.enabled = 1
                    ) AS total_capacity,
                    (
                        SELECT COUNT(*)
                        FROM registrations r
                        WHERE r.event_id = e.id
                          AND r.status = "confirmed"
                    ) AS registration_count
                FROM events e';

        if (!EntraAuth::isAdministrator()) {
            $sql .= ' WHERE e.admin_only = 0';
        }

        $sql .= ' ORDER BY e.event_date DESC, e.start_time DESC';

        $statement = $pdo->query($sql);
        $events = $statement->fetchAll();

        require dirname(__DIR__, 3)
            . '/templates/admin/dashboard.php';
    }
}
