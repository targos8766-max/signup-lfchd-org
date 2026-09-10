<?php

use Boneblaze\SignupLfchdOrg\Services\Csrf;

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
        <?= $registration['status'] === 'cancelled'
            ? 'Registration Cancelled'
            : 'Registration Confirmed' ?>
    </title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >
</head>

<body class="bg-light">

<div class="container py-5">

    <div class="row justify-content-center">
        <div class="col-lg-7">

            <div class="card shadow-sm">

                <div class="card-body p-5">

                    <div class="text-center mb-4">

                        <?php if ($registration['status'] === 'cancelled'): ?>

                            <h1 class="h2">
                                Registration Cancelled
                            </h1>

                            <p class="text-danger fw-semibold">
                                This registration has been cancelled.
                            </p>

                        <?php else: ?>

                            <h1 class="h2">
                                Registration Confirmed
                            </h1>

                            <p class="text-success fw-semibold">
                                Your signup has been successfully submitted.
                            </p>

                        <?php endif; ?>

                    </div>

                    <?php
                    $start = new DateTimeImmutable(
                        $registration['start_datetime']
                    );

                    $end = new DateTimeImmutable(
                        $registration['end_datetime']
                    );
                    ?>

                    <h2 class="h4">
                        <?= htmlspecialchars(
                            $registration['title'],
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </h2>

                    <hr>

                    <dl class="row">

                        <dt class="col-sm-4">
                            Name
                        </dt>

                        <dd class="col-sm-8">
                            <?= htmlspecialchars(
                                $registration['first_name']
                                . ' '
                                . $registration['last_name'],
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </dd>

                        <dt class="col-sm-4">
                            Date
                        </dt>

                        <dd class="col-sm-8">
                            <?= $start->format(
                                'l, F j, Y'
                            ) ?>
                        </dd>

                        <dt class="col-sm-4">
                            Time
                        </dt>

                        <dd class="col-sm-8">
                            <?= $start->format('g:i A') ?>
                            –
                            <?= $end->format('g:i A') ?>
                        </dd>

                        <?php if (!empty($registration['location'])): ?>

                            <dt class="col-sm-4">
                                Location
                            </dt>

                            <dd class="col-sm-8">
                                <?= htmlspecialchars(
                                    $registration['location'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </dd>

                        <?php endif; ?>

                    </dl>

                    <div class="alert alert-secondary mt-4">

                        Confirmation code:

                        <strong>
                            <?= htmlspecialchars(
                                $registration['confirmation_code'],
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </strong>

                    </div>

                    <?php if ($registration['status'] === 'confirmed'): ?>

                        <hr class="my-4">

                        <div class="border rounded p-4 bg-white">

                            <h2 class="h5">
                                Need to cancel?
                            </h2>

                            <p class="text-muted mb-3">
                                Cancelling your registration will release your
                                reserved time so someone else can register.
                            </p>

                            <form
                                method="post"
                                action="/event/<?= rawurlencode(
                                    $registration['public_slug']
                                ) ?>/confirmation/<?= rawurlencode(
                                    $registration['confirmation_code']
                                ) ?>/cancel"
                                onsubmit="return confirm(
                                    'Are you sure you want to cancel this registration?'
                                );"
                            >

                                <?= Csrf::field() ?>

                                <button
                                    type="submit"
                                    class="btn btn-outline-danger"
                                >
                                    Cancel Registration
                                </button>

                            </form>

                        </div>

                    <?php else: ?>

                        <div class="alert alert-warning mt-4 mb-0">
                            This registration is no longer active. The reserved
                            seat has been released.
                        </div>

                    <?php endif; ?>

                </div>

            </div>

        </div>
    </div>

</div>

</body>
</html>
