<?php
declare(strict_types=1);

$appUrl = rtrim((string) ($_ENV['APP_URL'] ?? ''), '/');
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Privacy Policy | LFCHD Signup</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >
    <link href="/css/signup.css" rel="stylesheet">
</head>
<body class="bg-light">

<header class="lfchd-public-header">
    <div class="container py-3">
        <div class="d-flex align-items-center gap-3">
            <img
                src="/assets/images/lfchd-logo.png"
                alt="Lexington-Fayette County Health Department"
                class="lfchd-public-logo"
            >
            <div>
                <div class="lfchd-public-title h4">
                    Lexington-Fayette County Health Department
                </div>
                <div class="text-muted">
                    Event Signup Privacy Policy
                </div>
            </div>
        </div>
    </div>
</header>

<main class="container py-4 py-md-5">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-9">
            <div class="card lfchd-page-card">
                <div class="card-body p-3 p-md-5">

                    <h1 class="h2 mb-3">Privacy Policy</h1>
                    <p class="text-muted">Last updated: September 14, 2026</p>

                    <p>
                        The Lexington-Fayette County Health Department (LFCHD)
                        uses this event signup system to register individuals
                        for public health events, appointments, classes, and
                        related activities.
                    </p>

                    <h2 class="h4 mt-4">Information We Collect</h2>
                    <p>
                        Depending on the event, LFCHD may collect information
                        such as your name, email address, mobile phone number,
                        preferred language, selected appointment or event time,
                        and answers to event-specific registration questions.
                    </p>

                    <h2 class="h4 mt-4">How We Use Information</h2>
                    <p>
                        Information submitted through this system is used to
                        administer your registration, communicate information
                        about the event or appointment, provide confirmations
                        and reminders, manage scheduling, and support related
                        LFCHD operations.
                    </p>

                    <h2 class="h4 mt-4">SMS and Mobile Information</h2>
                    <p>
                        If you voluntarily opt in to receive text messages,
                        LFCHD may send registration confirmations, reminders,
                        scheduling information, and event-related updates.
                        Message frequency varies depending on the event and
                        registration. Message and data rates may apply.
                    </p>

                    <p>
                        Mobile phone numbers and SMS consent information are
                        not sold, rented, or shared with third parties or
                        affiliates for their own marketing or promotional
                        purposes. Service providers that support delivery of
                        LFCHD communications may process information only as
                        needed to provide those services on LFCHD's behalf.
                    </p>

                    <p>
                        You may reply <strong>STOP</strong> to opt out of LFCHD
                        text messages or <strong>HELP</strong> for help. After
                        opting out, LFCHD will not intentionally send additional
                        SMS messages to that mobile number unless you opt in
                        again.
                    </p>

                    <h2 class="h4 mt-4">Email Communications</h2>
                    <p>
                        If you provide an email address, LFCHD may use it to
                        send registration confirmations, reminders,
                        cancellations, and event-related information.
                    </p>

                    <h2 class="h4 mt-4">Information Sharing</h2>
                    <p>
                        LFCHD does not sell registration information. Information
                        may be shared with authorized LFCHD personnel and
                        service providers when necessary to operate the signup
                        system, deliver communications, provide the registered
                        service, comply with law, or protect LFCHD systems and
                        operations.
                    </p>

                    <h2 class="h4 mt-4">Data Security and Retention</h2>
                    <p>
                        LFCHD uses reasonable administrative and technical
                        safeguards to protect information submitted through
                        this system. Information may be retained as needed for
                        operational, legal, records-management, and public-health
                        purposes.
                    </p>

                    <h2 class="h4 mt-4">Contact</h2>
                    <p>
                        Questions regarding this signup system or this privacy
                        policy may be directed to the Lexington-Fayette County
                        Health Department through the contact information
                        published on
                        <a href="https://www.lfchd.org/" target="_blank" rel="noopener">
                            lfchd.org
                        </a>.
                    </p>

                    <hr class="my-5">

                    <h1 class="h2 mb-3" lang="es">Política de privacidad</h1>

                    <p lang="es">
                        El Departamento de Salud del Condado de
                        Lexington-Fayette (LFCHD) utiliza este sistema para
                        registrar a las personas en eventos, citas, clases y
                        otras actividades de salud pública.
                    </p>

                    <h2 class="h4 mt-4" lang="es">
                        Información móvil y mensajes de texto
                    </h2>

                    <p lang="es">
                        Si usted acepta voluntariamente recibir mensajes de
                        texto, LFCHD puede enviar confirmaciones de registro,
                        recordatorios, información de programación y
                        actualizaciones relacionadas con el evento. La
                        frecuencia de los mensajes varía. Pueden aplicarse
                        tarifas de mensajes y datos.
                    </p>

                    <p lang="es">
                        Los números de teléfono móvil y la información de
                        consentimiento para SMS no se venden, alquilan ni
                        comparten con terceros o afiliados para sus propios
                        fines de mercadeo o promoción. Responda
                        <strong>STOP</strong> para dejar de recibir mensajes o
                        <strong>HELP</strong> para obtener ayuda.
                    </p>

                    <div class="mt-4">
                        <a href="/" class="btn btn-outline-primary">
                            Return to LFCHD Signup
                        </a>
                    </div>

                </div>
            </div>
        </div>
    </div>
</main>

<footer class="lfchd-footer">
    <div class="container py-3 text-center small">
        Lexington-Fayette County Health Department
        · <a href="/privacy">Privacy Policy</a>
        · <a href="/terms">Terms &amp; Conditions</a>
    </div>
</footer>

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
></script>
</body>
</html>
