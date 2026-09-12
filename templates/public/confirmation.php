<?php

use Boneblaze\SignupLfchdOrg\Services\Csrf;

$language =
    ($registration['preferred_language'] ?? 'en') === 'es'
        ? 'es'
        : 'en';

$isSpanish = $language === 'es';

$title =
    $isSpanish && !empty($registration['title_es'])
        ? $registration['title_es']
        : $registration['title'];

$description =
    $isSpanish && !empty($registration['description_es'])
        ? $registration['description_es']
        : ($registration['description'] ?? '');

$location =
    $isSpanish && !empty($registration['location_es'])
        ? $registration['location_es']
        : ($registration['location'] ?? '');

$start = new DateTimeImmutable(
    $registration['start_datetime']
);

$end = new DateTimeImmutable(
    $registration['end_datetime']
);

function confirmationDate(
    DateTimeImmutable $date,
    string $language
): string {
    if ($language !== 'es') {
        return $date->format('l, F j, Y');
    }

    $days = [
        'Sunday' => 'domingo',
        'Monday' => 'lunes',
        'Tuesday' => 'martes',
        'Wednesday' => 'miércoles',
        'Thursday' => 'jueves',
        'Friday' => 'viernes',
        'Saturday' => 'sábado',
    ];

    $months = [
        'January' => 'enero',
        'February' => 'febrero',
        'March' => 'marzo',
        'April' => 'abril',
        'May' => 'mayo',
        'June' => 'junio',
        'July' => 'julio',
        'August' => 'agosto',
        'September' => 'septiembre',
        'October' => 'octubre',
        'November' => 'noviembre',
        'December' => 'diciembre',
    ];

    return $days[$date->format('l')]
        . ', '
        . $date->format('j')
        . ' de '
        . $months[$date->format('F')]
        . ' de '
        . $date->format('Y');
}

$copy = $isSpanish
    ? [
        'page_title' => 'Confirmación de registro',
        'confirmed' => 'Registro confirmado',
        'cancelled' => 'Registro cancelado',
        'cancelled_notice' => 'Su registro ha sido cancelado.',
        'thank_you' => 'Gracias, su registro está confirmado.',
        'event' => 'Evento',
        'date' => 'Fecha',
        'time' => 'Hora',
        'location' => 'Ubicación',
        'name' => 'Nombre',
        'email' => 'Correo electrónico',
        'phone' => 'Teléfono',
        'cancel_heading' => 'Cancelar registro',
        'cancel_text' => 'Si ya no puede asistir, puede cancelar su registro aquí.',
        'cancel_button' => 'Cancelar mi registro',
        'cancel_confirm' => '¿Está seguro de que desea cancelar este registro?',
    ]
    : [
        'page_title' => 'Registration Confirmation',
        'confirmed' => 'Registration Confirmed',
        'cancelled' => 'Registration Cancelled',
        'cancelled_notice' => 'Your registration has been cancelled.',
        'thank_you' => 'Thank you. Your registration is confirmed.',
        'event' => 'Event',
        'date' => 'Date',
        'time' => 'Time',
        'location' => 'Location',
        'name' => 'Name',
        'email' => 'Email',
        'phone' => 'Phone',
        'cancel_heading' => 'Cancel Registration',
        'cancel_text' => 'If you can no longer attend, you can cancel your registration here.',
        'cancel_button' => 'Cancel My Registration',
        'cancel_confirm' => 'Are you sure you want to cancel this registration?',
    ];

$isCancelled =
    ($registration['status'] ?? '') === 'cancelled';

?>

<!doctype html>
<html lang="<?= $language ?>">
<head>
    <meta charset="utf-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <title>
        <?= htmlspecialchars(
            $copy['page_title'],
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

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-7">

            <?php if (!empty($_GET['cancelled'])): ?>

                <div class="alert alert-success">
                    <?= htmlspecialchars(
                        $copy['cancelled_notice'],
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </div>

            <?php endif; ?>

            <div class="card shadow-sm">

                <div class="card-body p-4">

                    <h1 class="h3 mb-3">
                        <?= htmlspecialchars(
                            $isCancelled
                                ? $copy['cancelled']
                                : $copy['confirmed'],
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </h1>

                    <?php if (!$isCancelled): ?>

                        <p class="lead">
                            <?= htmlspecialchars(
                                $copy['thank_you'],
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </p>

                    <?php endif; ?>

                    <?php if ($description !== ''): ?>

                        <p>
                            <?= nl2br(
                                htmlspecialchars(
                                    $description,
                                    ENT_QUOTES,
                                    'UTF-8'
                                )
                            ) ?>
                        </p>

                    <?php endif; ?>

                    <dl class="row mb-0">

                        <dt class="col-sm-4">
                            <?= htmlspecialchars($copy['event']) ?>
                        </dt>

                        <dd class="col-sm-8">
                            <?= htmlspecialchars(
                                $title,
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </dd>

                        <dt class="col-sm-4">
                            <?= htmlspecialchars($copy['date']) ?>
                        </dt>

                        <dd class="col-sm-8">
                            <?= htmlspecialchars(
                                confirmationDate(
                                    $start,
                                    $language
                                ),
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </dd>

                        <dt class="col-sm-4">
                            <?= htmlspecialchars($copy['time']) ?>
                        </dt>

                        <dd class="col-sm-8">
                            <?= $start->format('g:i A') ?>
                            –
                            <?= $end->format('g:i A') ?>
                        </dd>

                        <?php if ($location !== ''): ?>

                            <dt class="col-sm-4">
                                <?= htmlspecialchars($copy['location']) ?>
                            </dt>

                            <dd class="col-sm-8">
                                <?= htmlspecialchars(
                                    $location,
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </dd>

                        <?php endif; ?>

                        <dt class="col-sm-4">
                            <?= htmlspecialchars($copy['name']) ?>
                        </dt>

                        <dd class="col-sm-8">
                            <?= htmlspecialchars(
                                trim(
                                    ($registration['first_name'] ?? '')
                                    . ' '
                                    . ($registration['last_name'] ?? '')
                                ),
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </dd>

                        <?php if (!empty($registration['email'])): ?>

                            <dt class="col-sm-4">
                                <?= htmlspecialchars($copy['email']) ?>
                            </dt>

                            <dd class="col-sm-8">
                                <?= htmlspecialchars(
                                    $registration['email'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </dd>

                        <?php endif; ?>

                        <?php if (!empty($registration['phone'])): ?>

                            <dt class="col-sm-4">
                                <?= htmlspecialchars($copy['phone']) ?>
                            </dt>

                            <dd class="col-sm-8">
                                <?= htmlspecialchars(
                                    $registration['phone'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </dd>

                        <?php endif; ?>

                    </dl>

                </div>

                <?php if (!$isCancelled): ?>

                    <div class="card-footer p-4">

                        <h2 class="h5">
                            <?= htmlspecialchars(
                                $copy['cancel_heading'],
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </h2>

                        <p class="mb-3">
                            <?= htmlspecialchars(
                                $copy['cancel_text'],
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </p>

                        <form
                            method="post"
                            action="/event/<?= rawurlencode(
                                $registration['public_slug']
                            ) ?>/confirmation/<?= rawurlencode(
                                $registration['confirmation_code']
                            ) ?>/cancel"
                            onsubmit="return confirm(<?= json_encode(
                                $copy['cancel_confirm'],
                                JSON_HEX_TAG
                                | JSON_HEX_APOS
                                | JSON_HEX_AMP
                                | JSON_HEX_QUOT
                            ) ?>);"
                        >

                            <?= Csrf::field() ?>

                            <button
                                type="submit"
                                class="btn btn-outline-danger"
                            >
                                <?= htmlspecialchars(
                                    $copy['cancel_button'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </button>

                        </form>

                    </div>

                <?php endif; ?>

            </div>

        </div>
    </div>
</div>

</body>
</html>
