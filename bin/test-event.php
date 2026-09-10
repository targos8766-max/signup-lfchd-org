<?php

declare(strict_types=1);

use Boneblaze\SignupLfchdOrg\Database\Database;
use Boneblaze\SignupLfchdOrg\Models\Event;
use Boneblaze\SignupLfchdOrg\Services\SlotGenerator;
use Dotenv\Dotenv;

require dirname(__DIR__) . '/vendor/autoload.php';

$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

$pdo = Database::connection();

$eventModel = new Event($pdo);
$slotGenerator = new SlotGenerator($pdo);

$slug = bin2hex(random_bytes(8));

$eventId = $eventModel->create([
    'title' => 'Test Signup Event',
    'public_slug' => $slug,
    'description' => 'Initial slot generation test',
    'location' => 'Test Location',
    'event_date' => '2026-09-15',
    'start_time' => '11:00:00',
    'end_time' => '15:00:00',
    'interval_minutes' => 15,
    'default_capacity' => 4,
    'status' => 'draft',
    'created_by' => 'development-test',
]);

$count = $slotGenerator->generateForEvent(
    $eventId,
    '2026-09-15',
    '11:00:00',
    '15:00:00',
    15,
    4
);

echo "Created event ID: {$eventId}\n";
echo "Public slug: {$slug}\n";
echo "Created slots: {$count}\n";