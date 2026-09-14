<?php
$language = ($registration['preferred_language'] ?? 'en') === 'es' ? 'es' : 'en';

$eventTitle = trim((string)($language === 'es' ? ($event['title_es'] ?? '') : ''));
if ($eventTitle === '') {
    $eventTitle = (string)($event['title'] ?? '');
}

$eventLocation = trim((string)($language === 'es' ? ($event['location_es'] ?? '') : ''));
if ($eventLocation === '') {
    $eventLocation = (string)($event['location'] ?? '');
}

function publicConfirmationDate(string $dateTime, string $language): string
{
    $dt = new DateTimeImmutable($dateTime);

    if ($language !== 'es') {
        return $dt->format('l, F j, Y');
    }

    $days = [
        'Sunday' => 'domingo', 'Monday' => 'lunes', 'Tuesday' => 'martes',
        'Wednesday' => 'miércoles', 'Thursday' => 'jueves',
        'Friday' => 'viernes', 'Saturday' => 'sábado',
    ];

    $months = [
        1 => 'enero', 2 => 'febrero', 3 => 'marzo', 4 => 'abril',
        5 => 'mayo', 6 => 'junio', 7 => 'julio', 8 => 'agosto',
        9 => 'septiembre', 10 => 'octubre', 11 => 'noviembre', 12 => 'diciembre',
    ];

    return sprintf(
        '%s, %d de %s de %s',
        $days[$dt->format('l')] ?? $dt->format('l'),
        (int)$dt->format('j'),
        $months[(int)$dt->format('n')] ?? $dt->format('F'),
        $dt->format('Y')
    );
}

$labels = $language === 'es'
    ? [
        'site_name' => 'Registro de eventos',
        'confirmed' => 'Registro confirmado',
        'cancelled' => 'Registro cancelado',
        'thank_you' => 'Gracias. Su registro está confirmado.',
        'already_cancelled' => 'Este registro ha sido cancelado.',
        'cancel_success' => 'Su registro ha sido cancelado correctamente.',
        'event' => 'Evento',
        'date' => 'Fecha',
        'time' => 'Hora',
        'location' => 'Lugar',
        'name' => 'Nombre',
        'email' => 'Correo electrónico',
        'phone' => 'Teléfono',
        'cancel_heading' => '¿Necesita cancelar?',
        'cancel_text' => 'Puede cancelar este registro utilizando el botón a continuación.',
        'cancel_button' => 'Cancelar registro',
        'cancel_confirm' => '¿Está seguro de que desea cancelar este registro?',
    ]
    : [
        'site_name' => 'Event Registration',
        'confirmed' => 'Registration Confirmed',
        'cancelled' => 'Registration Cancelled',
        'thank_you' => 'Thank you. Your registration is confirmed.',
        'already_cancelled' => 'This registration has been cancelled.',
        'cancel_success' => 'Your registration has been cancelled successfully.',
        'event' => 'Event',
        'date' => 'Date',
        'time' => 'Time',
        'location' => 'Location',
        'name' => 'Name',
        'email' => 'Email',
        'phone' => 'Phone',
        'cancel_heading' => 'Need to cancel?',
        'cancel_text' => 'You can cancel this registration using the button below.',
        'cancel_button' => 'Cancel Registration',
        'cancel_confirm' => 'Are you sure you want to cancel this registration?',
    ];

$isCancelled = ($registration['status'] ?? '') === 'cancelled';
?>
<!doctype html>
<html lang="<?= $language === 'es' ? 'es' : 'en' ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($isCancelled ? $labels['cancelled'] : $labels['confirmed'], ENT_QUOTES, 'UTF-8') ?> | LFCHD</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/css/signup.css" rel="stylesheet">
</head>
<body class="bg-body-tertiary">

<header class="lfchd-public-header">
    <div class="container py-3 py-md-4">
        <div class="d-flex align-items-center gap-3 gap-md-4">
            <img
                src="/assets/images/lfchd-logo.png"
                alt="Lexington-Fayette County Health Department"
                class="lfchd-public-logo"
            >
            <div>
                <div class="lfchd-public-department-name">
                    Lexington-Fayette County Health Department
                </div>
                <div class="lfchd-public-site-name">
                    <?= htmlspecialchars($labels['site_name'], ENT_QUOTES, 'UTF-8') ?>
                </div>
            </div>
        </div>
    </div>
</header>

<main class="container py-4 py-md-5">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-8 col-xl-7">

            <?php if (isset($_GET['cancelled']) && $_GET['cancelled'] === '1'): ?>
                <div class="alert alert-success">
                    <?= htmlspecialchars($labels['cancel_success'], ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php endif; ?>

            <section class="card lfchd-page-card">
                <div class="card-body p-4 p-md-5">

                    <div class="text-center mb-4">
                        <div class="lfchd-confirmation-icon <?= $isCancelled ? 'is-cancelled' : '' ?>" aria-hidden="true">
                            <?= $isCancelled ? '×' : '✓' ?>
                        </div>

                        <h1 class="mt-3 mb-2">
                            <?= htmlspecialchars($isCancelled ? $labels['cancelled'] : $labels['confirmed'], ENT_QUOTES, 'UTF-8') ?>
                        </h1>

                        <p class="text-secondary mb-0">
                            <?= htmlspecialchars($isCancelled ? $labels['already_cancelled'] : $labels['thank_you'], ENT_QUOTES, 'UTF-8') ?>
                        </p>
                    </div>

                    <div class="lfchd-confirmation-details">
                        <div class="lfchd-confirmation-row">
                            <div class="lfchd-confirmation-label"><?= htmlspecialchars($labels['event'], ENT_QUOTES, 'UTF-8') ?></div>
                            <div class="lfchd-confirmation-value"><?= htmlspecialchars($eventTitle, ENT_QUOTES, 'UTF-8') ?></div>
                        </div>

                        <div class="lfchd-confirmation-row">
                            <div class="lfchd-confirmation-label"><?= htmlspecialchars($labels['date'], ENT_QUOTES, 'UTF-8') ?></div>
                            <div class="lfchd-confirmation-value"><?= htmlspecialchars(publicConfirmationDate((string)$slot['start_datetime'], $language), ENT_QUOTES, 'UTF-8') ?></div>
                        </div>

                        <div class="lfchd-confirmation-row">
                            <div class="lfchd-confirmation-label"><?= htmlspecialchars($labels['time'], ENT_QUOTES, 'UTF-8') ?></div>
                            <div class="lfchd-confirmation-value"><?= htmlspecialchars((new DateTimeImmutable((string)$slot['start_datetime']))->format('g:i A'), ENT_QUOTES, 'UTF-8') ?></div>
                        </div>

                        <?php if ($eventLocation !== ''): ?>
                            <div class="lfchd-confirmation-row">
                                <div class="lfchd-confirmation-label"><?= htmlspecialchars($labels['location'], ENT_QUOTES, 'UTF-8') ?></div>
                                <div class="lfchd-confirmation-value"><?= htmlspecialchars($eventLocation, ENT_QUOTES, 'UTF-8') ?></div>
                            </div>
                        <?php endif; ?>

                        <div class="lfchd-confirmation-row">
                            <div class="lfchd-confirmation-label"><?= htmlspecialchars($labels['name'], ENT_QUOTES, 'UTF-8') ?></div>
                            <div class="lfchd-confirmation-value">
                                <?= htmlspecialchars(trim((string)($registration['first_name'] ?? '') . ' ' . (string)($registration['last_name'] ?? '')), ENT_QUOTES, 'UTF-8') ?>
                            </div>
                        </div>

                        <?php if (!empty($registration['email'])): ?>
                            <div class="lfchd-confirmation-row">
                                <div class="lfchd-confirmation-label"><?= htmlspecialchars($labels['email'], ENT_QUOTES, 'UTF-8') ?></div>
                                <div class="lfchd-confirmation-value text-break"><?= htmlspecialchars((string)$registration['email'], ENT_QUOTES, 'UTF-8') ?></div>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($registration['phone'])): ?>
                            <div class="lfchd-confirmation-row">
                                <div class="lfchd-confirmation-label"><?= htmlspecialchars($labels['phone'], ENT_QUOTES, 'UTF-8') ?></div>
                                <div class="lfchd-confirmation-value"><?= htmlspecialchars((string)$registration['phone'], ENT_QUOTES, 'UTF-8') ?></div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php if (!$isCancelled): ?>
                        <div class="lfchd-cancel-panel mt-4">
                            <h2 class="h5 mb-2"><?= htmlspecialchars($labels['cancel_heading'], ENT_QUOTES, 'UTF-8') ?></h2>
                            <p class="text-secondary mb-3"><?= htmlspecialchars($labels['cancel_text'], ENT_QUOTES, 'UTF-8') ?></p>

                            <form
                                method="post"
                                action="/event/<?= rawurlencode((string)$event['public_slug']) ?>/confirmation/<?= rawurlencode((string)$registration['confirmation_code']) ?>/cancel"
                                onsubmit="return confirm(<?= json_encode($labels['cancel_confirm'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>);"
                            >
                                <?= \Boneblaze\SignupLfchdOrg\Services\Csrf::field() ?>
                                <button type="submit" class="btn btn-outline-danger">
                                    <?= htmlspecialchars($labels['cancel_button'], ENT_QUOTES, 'UTF-8') ?>
                                </button>
                            </form>
                        </div>
                    <?php endif; ?>

                </div>
            </section>

        </div>
    </div>
</main>

<footer class="lfchd-footer">
    <div class="container py-4">
        <div class="text-center small">
            Lexington-Fayette County Health Department
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
