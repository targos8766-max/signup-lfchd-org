<?php

use Boneblaze\SignupLfchdOrg\Services\EntraAuth;

$authUser = EntraAuth::user();

$pageTitle = $pageTitle ?? 'LFCHD Signup';

$isAdministrator =
    EntraAuth::isAdministrator();

$isRegistrationManager =
    EntraAuth::isRegistrationManager();

?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <title>
        <?= htmlspecialchars(
            $pageTitle,
            ENT_QUOTES,
            'UTF-8'
        ) ?>
    </title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >
</head>

<body class="bg-light">

<nav class="navbar navbar-dark bg-dark mb-4">
    <div class="container">

        <div class="d-flex align-items-center gap-3">
            <a
                class="navbar-brand mb-0"
                href="/admin"
            >
                LFCHD Signup
            </a>

            <a
                href="/admin"
                class="btn btn-outline-light btn-sm"
            >
                Events
            </a>

            <?php if ($isAdministrator): ?>

                <a
                    href="/admin/events/create"
                    class="btn btn-success btn-sm"
                >
                    Create Event
                </a>

            <?php endif; ?>
        </div>

        <div class="d-flex align-items-center gap-3">

            <div class="text-end">

                <div class="text-light small">
                    <?= htmlspecialchars(
                        $authUser['name']
                            ?? $authUser['email']
                            ?? 'Signed in',
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </div>

                <div class="text-secondary small">

                    <?php if ($isAdministrator): ?>

                        Administrator

                    <?php elseif ($isRegistrationManager): ?>

                        Registration Manager

                    <?php endif; ?>

                </div>

            </div>

            <a
                href="/logout"
                class="btn btn-outline-light btn-sm"
            >
                Logout
            </a>

        </div>

    </div>
</nav>

<main class="container mb-5">
