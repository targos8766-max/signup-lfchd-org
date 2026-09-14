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

    <link
        href="/css/signup.css"
        rel="stylesheet"
    >
</head>

<body class="bg-body-tertiary">

<nav class="navbar navbar-expand-lg lfchd-admin-header">
    <div class="container-fluid px-3 px-lg-4">

        <a
            class="navbar-brand d-flex align-items-center gap-3 me-lg-5"
            href="/admin"
        >
            <img
                src="/assets/images/lfchd-logo.png"
                alt="Lexington-Fayette County Health Department"
                class="lfchd-admin-logo"
            >

            <div class="d-none d-sm-block">
                <div class="lfchd-admin-brand-title">
                    LFCHD Signup
                </div>

                <div class="lfchd-admin-brand-subtitle">
                    Lexington-Fayette County Health Department
                </div>
            </div>
        </a>

        <button
            class="navbar-toggler ms-auto"
            type="button"
            data-bs-toggle="collapse"
            data-bs-target="#lfchdAdminNavigation"
            aria-controls="lfchdAdminNavigation"
            aria-expanded="false"
            aria-label="Toggle navigation"
        >
            <span class="navbar-toggler-icon"></span>
        </button>

        <div
            class="collapse navbar-collapse"
            id="lfchdAdminNavigation"
        >
            <div class="navbar-nav me-auto align-items-lg-center gap-lg-2 mt-3 mt-lg-0">

                <a
                    href="/admin"
                    class="nav-link lfchd-primary-nav-link"
                >
                    <span class="lfchd-nav-icon" aria-hidden="true">▣</span>
                    <span>Events</span>
                </a>

                <?php if ($isAdministrator): ?>

                    <a
                        href="/admin/events/create"
                        class="nav-link lfchd-secondary-nav-link"
                    >
                        <span class="lfchd-nav-plus" aria-hidden="true">+</span>
                        <span>Create Event</span>
                    </a>

                <?php endif; ?>

            </div>

            <div class="lfchd-account-area d-flex flex-column flex-lg-row align-items-lg-center gap-3 mt-3 mt-lg-0">

                <div class="lfchd-account-divider d-none d-lg-block"></div>

                <div class="d-flex align-items-start gap-3">
                    <div class="lfchd-user-icon d-none d-md-flex" aria-hidden="true">
                        ●
                    </div>

                    <div class="text-lg-start">

                        <div class="lfchd-user-name">
                            <?= htmlspecialchars(
                                $authUser['name']
                                    ?? $authUser['email']
                                    ?? 'Signed in',
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </div>

                        <div class="lfchd-user-role">

                            <?php if ($isAdministrator): ?>

                                Administrator

                            <?php elseif ($isRegistrationManager): ?>

                                Registration Manager

                            <?php endif; ?>

                        </div>

                    </div>
                </div>

                <a
                    href="/logout"
                    class="btn lfchd-logout-btn"
                >
                    Logout
                </a>

            </div>
        </div>
    </div>
</nav>

<main class="container-fluid px-3 px-lg-4 py-4">
