<?php

declare(strict_types=1);

use Boneblaze\SignupLfchdOrg\Database\Database;
use Dotenv\Dotenv;

require dirname(__DIR__) . '/vendor/autoload.php';

$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

try {
    $pdo = Database::connection();

    $result = $pdo->query('SELECT VERSION() AS version')->fetch();

    echo '<h1>Signup LFCHD</h1>';
    echo '<p>Application is running.</p>';
    echo '<p>Database connection successful.</p>';
    echo '<p>MySQL version: ' . htmlspecialchars($result['version']) . '</p>';
} catch (Throwable $e) {
    http_response_code(500);

    echo '<h1>Signup LFCHD</h1>';
    echo '<p>Database connection failed.</p>';

    if (($_ENV['APP_ENV'] ?? '') === 'staging') {
        echo '<pre>' . htmlspecialchars($e->getMessage()) . '</pre>';
    }
}