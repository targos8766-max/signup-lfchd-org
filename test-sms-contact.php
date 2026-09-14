<?php

declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

$service =
    new Boneblaze\SignupLfchdOrg\Services\SmsContactService();

$phone = '859-555-0100';

echo 'Normalized: '
    . $service->normalizePhoneNumber($phone)
    . PHP_EOL;

echo 'Opted out: '
    . ($service->isOptedOut($phone) ? 'YES' : 'NO')
    . PHP_EOL;

echo 'Can receive SMS: '
    . ($service->canReceiveSms($phone) ? 'YES' : 'NO')
    . PHP_EOL;
