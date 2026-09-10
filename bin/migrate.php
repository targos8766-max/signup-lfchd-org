<?php

declare(strict_types=1);

use Boneblaze\SignupLfchdOrg\Database\Database;
use Dotenv\Dotenv;

require dirname(__DIR__) . '/vendor/autoload.php';

$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

$pdo = Database::connection();

$pdo->exec("
    CREATE TABLE IF NOT EXISTS migrations (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        migration VARCHAR(255) NOT NULL UNIQUE,
        executed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

$migrationDirectory = dirname(__DIR__) . '/database/migrations';

$files = glob($migrationDirectory . '/*.sql');
sort($files);

foreach ($files as $file) {
    $migration = basename($file);

    $statement = $pdo->prepare(
        'SELECT COUNT(*) FROM migrations WHERE migration = ?'
    );

    $statement->execute([$migration]);

    if ((int) $statement->fetchColumn() > 0) {
        echo "Skipping: {$migration}\n";
        continue;
    }

    echo "Running: {$migration}\n";

    $sql = file_get_contents($file);

    if ($sql === false) {
        throw new RuntimeException("Unable to read migration: {$migration}");
    }

    try {
        $pdo->exec($sql);

        $statement = $pdo->prepare(
            'INSERT INTO migrations (migration) VALUES (?)'
        );

        $statement->execute([$migration]);

        echo "Completed: {$migration}\n";
    } catch (Throwable $e) {
        echo "FAILED: {$migration}\n";
        echo $e->getMessage() . "\n";

        exit(1);
    }
}

echo "Migrations complete.\n";