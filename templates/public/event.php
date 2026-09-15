<?php
$language = $language ?? ($old['preferred_language'] ?? 'en');
$language = $language === 'es' ? 'es' : 'en';

$eventTitle = trim((string)($language === 'es' ? ($event['title_es'] ?? '') : ''));
if ($eventTitle === '') {
    $eventTitle = (string)($event['title'] ?? '');
}

$eventDescription = trim((string)($language === 'es' ? ($event['description_es'] ?? '') : ''));
if ($eventDescription === '') {
    $eventDescription = (string)($event['description'] ?? '');
}

$eventLocation = trim((string)($language === 'es' ? ($event['location_es'] ?? '') : ''));
if ($eventLocation === '') {
    $eventLocation = (string)($event['location'] ?? '');
}

function publicEventDate(string $date, string $language): string
{
    $dt = new DateTimeImmutable($date);

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

function questionLabel(array $question, string $language): string
{
    if ($language === 'es') {
        $spanish = trim((string)($question['question_text_es'] ?? ''));
        if ($spanish !== '') {
            return $spanish;
        }
    }

    return (string)($question['question_text'] ?? '');
}

function questionOptions(array $question): array
{
    $english = [];
    $spanish = [];

    if (!empty($question['options_json'])) {
        $decoded = json_decode((string)$question['options_json'], true);
        if (is_array($decoded)) {
            $english = array_values($decoded);
        }
    }

    if (!empty($question['options_json_es'])) {
        $decoded = json_decode((string)$question['options_json_es'], true);
        if (is_array($decoded)) {
            $spanish = array_values($decoded);
        }
    }

    return [$english, $spanish];
}

$labels = $language === 'es'
    ? [
        'date' => 'Fecha',
        'location' => 'Lugar',
        'select_time' => 'Seleccione una hora',
        'choose_time' => 'Elija una hora disponible',
        'seats' => 'lugares disponibles',
        'seat' => 'lugar disponible',
        'full' => 'Lleno',
        'registration_info' => 'Su información',
        'first_name' => 'Nombre',
        'last_name' => 'Apellido',
        'email' => 'Correo electrónico',
        'phone' => 'Teléfono',
        'sms_opt_in' => 'Acepto recibir mensajes de texto de LFCHD relacionados con este registro.',
        'sms_note' => 'Los mensajes pueden incluir confirmaciones de registro, recordatorios, información de programación, cancelaciones y actualizaciones relacionadas con el evento. La frecuencia de los mensajes varía según su registro. Puede recibir hasta 5 mensajes SMS por cada registro de evento. Pueden aplicarse tarifas de mensajes y datos. Responda STOP para dejar de recibir mensajes o HELP para obtener ayuda. El consentimiento para recibir mensajes de texto es opcional y no es necesario para registrarse.',
        'additional_info' => 'Información adicional',
        'required' => 'Obligatorio',
        'submit' => 'Registrarme',
        'not_open' => 'El registro no está disponible en este momento.',
        'select_one' => 'Seleccione una opción',
    ]
    : [
        'date' => 'Date',
        'location' => 'Location',
        'select_time' => 'Select a Time',
        'choose_time' => 'Choose an available time',
        'seats' => 'seats available',
        'seat' => 'seat available',
        'full' => 'Full',
        'registration_info' => 'Your Information',
        'first_name' => 'First Name',
        'last_name' => 'Last Name',
        'email' => 'Email',
        'phone' => 'Phone',
        'sms_opt_in' => 'I agree to receive text messages from LFCHD at the mobile phone number provided above related to this registration.',
        'sms_note' => 'Messages may include registration confirmations, reminders, scheduling information, cancellations, and event-related updates. Message frequency varies based on your registration. You may receive up to 5 SMS messages per event registration. Message and data rates may apply. Reply STOP to opt out or HELP for help. Consent to receive text messages is optional and is not required to register.',
        'additional_info' => 'Additional Information',
        'required' => 'Required',
        'submit' => 'Register',
        'not_open' => 'Registration is not available at this time.',
        'select_one' => 'Select an option',
    ];

$old = $old ?? [];
$errors = $errors ?? [];
$questions = $questions ?? [];
$slots = $slots ?? [];
$registrationStatus = $registrationStatus ?? ['open' => true, 'message' => ''];
?>
<!doctype html>
<html lang="<?= $language === 'es' ? 'es' : 'en' ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($eventTitle, ENT_QUOTES, 'UTF-8') ?> | LFCHD</title>
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
                    <?= $language === 'es' ? 'Registro de eventos' : 'Event Registration' ?>
                </div>
            </div>
        </div>
    </div>
</header>

<main class="container py-4 py-md-5">
    <div class="row justify-content-center">
        <div class="col-12 col-xl-10 col-xxl-9">

            <div class="d-flex justify-content-end mb-3">
                <div class="lfchd-language-switcher">
                    <label for="public-language" class="form-label small mb-1">
                        Preferred Language / Idioma preferido
                    </label>
                    <select
                        id="public-language"
                        class="form-select"
                        onchange="window.location.href='?lang=' + encodeURIComponent(this.value);"
                    >
                        <option value="en" <?= $language === 'en' ? 'selected' : '' ?>>English</option>
                        <option value="es" <?= $language === 'es' ? 'selected' : '' ?>>Español</option>
                    </select>
                </div>
            </div>

            <section class="card lfchd-page-card mb-4">
                <div class="card-body p-4 p-md-5">
                    <h1 class="lfchd-event-title mb-3">
                        <?= htmlspecialchars($eventTitle, ENT_QUOTES, 'UTF-8') ?>
                    </h1>

                    <?php if ($eventDescription !== ''): ?>
                        <div class="lfchd-event-description mb-4">
                            <?= nl2br(htmlspecialchars($eventDescription, ENT_QUOTES, 'UTF-8')) ?>
                        </div>
                    <?php endif; ?>

                    <div class="row g-3 lfchd-event-meta">
                        <div class="col-12 col-md-6">
                            <div class="lfchd-meta-label">
                                <?= htmlspecialchars($labels['date'], ENT_QUOTES, 'UTF-8') ?>
                            </div>
                            <div class="lfchd-meta-value">
                                <?= htmlspecialchars(publicEventDate((string)$event['event_date'], $language), ENT_QUOTES, 'UTF-8') ?>
                            </div>
                        </div>

                        <?php if ($eventLocation !== ''): ?>
                            <div class="col-12 col-md-6">
                                <div class="lfchd-meta-label">
                                    <?= htmlspecialchars($labels['location'], ENT_QUOTES, 'UTF-8') ?>
                                </div>
                                <div class="lfchd-meta-value">
                                    <?= htmlspecialchars($eventLocation, ENT_QUOTES, 'UTF-8') ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </section>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger" role="alert">
                    <ul class="mb-0 ps-3">
                        <?php foreach ($errors as $error): ?>
                            <li><?= htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if (empty($registrationStatus['open'])): ?>
                <div class="alert alert-info lfchd-page-card">
                    <?= htmlspecialchars((string)($registrationStatus['message'] ?: $labels['not_open']), ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php else: ?>
                <form
                    method="post"
                    action="/event/<?= rawurlencode((string)$event['public_slug']) ?>/register"
                    class="card lfchd-page-card"
                    id="registration-form"
                >
                    <div class="card-body p-4 p-md-5">
                        <?= \Boneblaze\SignupLfchdOrg\Services\Csrf::field() ?>

                        <input type="hidden" name="preferred_language" value="<?= htmlspecialchars($language, ENT_QUOTES, 'UTF-8') ?>">

                        <section class="mb-5">
                            <h2 class="lfchd-section-heading">
                                <?= htmlspecialchars($labels['select_time'], ENT_QUOTES, 'UTF-8') ?>
                            </h2>
                            <p class="text-secondary mb-3">
                                <?= htmlspecialchars($labels['choose_time'], ENT_QUOTES, 'UTF-8') ?>
                            </p>

                            <div class="row g-3">
                                <?php foreach ($slots as $slot): ?>
                                    <?php
                                    $available = (int)($slot['available'] ?? 0);
                                    $slotDisabled = empty($slot['enabled']) || $available <= 0;
                                    $slotId = (string)$slot['id'];
                                    $selectedSlot = (string)($old['slot_id'] ?? '');
                                    ?>
                                    <div class="col-12 col-sm-6 col-lg-4">
                                        <label class="lfchd-slot-card <?= $slotDisabled ? 'is-disabled' : '' ?>">
                                            <input
                                                type="radio"
                                                name="slot_id"
                                                value="<?= htmlspecialchars($slotId, ENT_QUOTES, 'UTF-8') ?>"
                                                <?= $selectedSlot === $slotId ? 'checked' : '' ?>
                                                <?= $slotDisabled ? 'disabled' : '' ?>
                                                required
                                            >
                                            <span class="lfchd-slot-time">
                                                <?= htmlspecialchars((new DateTimeImmutable((string)$slot['start_datetime']))->format('g:i A'), ENT_QUOTES, 'UTF-8') ?>
                                            </span>
                                            <span class="lfchd-slot-availability">
                                                <?php if ($slotDisabled): ?>
                                                    <?= htmlspecialchars($labels['full'], ENT_QUOTES, 'UTF-8') ?>
                                                <?php else: ?>
                                                    <?= $available ?> <?= htmlspecialchars($available === 1 ? $labels['seat'] : $labels['seats'], ENT_QUOTES, 'UTF-8') ?>
                                                <?php endif; ?>
                                            </span>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </section>

                        <section class="mb-5">
                            <h2 class="lfchd-section-heading">
                                <?= htmlspecialchars($labels['registration_info'], ENT_QUOTES, 'UTF-8') ?>
                            </h2>

                            <div class="row g-3">
                                <div class="col-12 col-md-6">
                                    <label class="form-label" for="first_name">
                                        <?= htmlspecialchars($labels['first_name'], ENT_QUOTES, 'UTF-8') ?>
                                    </label>
                                    <input
                                        class="form-control"
                                        id="first_name"
                                        name="first_name"
                                        type="text"
                                        autocomplete="given-name"
                                        value="<?= htmlspecialchars((string)($old['first_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                        required
                                    >
                                </div>

                                <div class="col-12 col-md-6">
                                    <label class="form-label" for="last_name">
                                        <?= htmlspecialchars($labels['last_name'], ENT_QUOTES, 'UTF-8') ?>
                                    </label>
                                    <input
                                        class="form-control"
                                        id="last_name"
                                        name="last_name"
                                        type="text"
                                        autocomplete="family-name"
                                        value="<?= htmlspecialchars((string)($old['last_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                        required
                                    >
                                </div>

                                <div class="col-12 col-md-6">
                                    <label class="form-label" for="email">
                                        <?= htmlspecialchars($labels['email'], ENT_QUOTES, 'UTF-8') ?>
                                    </label>
                                    <input
                                        class="form-control"
                                        id="email"
                                        name="email"
                                        type="email"
                                        autocomplete="email"
                                        inputmode="email"
                                        value="<?= htmlspecialchars((string)($old['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                    >
                                </div>

                                <div class="col-12 col-md-6">
                                    <label class="form-label" for="phone">
                                        <?= htmlspecialchars($labels['phone'], ENT_QUOTES, 'UTF-8') ?>
                                    </label>
                                    <input
                                        class="form-control"
                                        id="phone"
                                        name="phone"
                                        type="tel"
                                        autocomplete="tel"
                                        inputmode="tel"
                                        value="<?= htmlspecialchars((string)($old['phone'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                    >
                                </div>

                                <div class="col-12">
                                    <div class="form-check lfchd-sms-consent">
                                        <input
                                            class="form-check-input"
                                            id="sms_opt_in"
                                            name="sms_opt_in"
                                            type="checkbox"
                                            value="1"
                                            <?= !empty($old['sms_opt_in']) ? 'checked' : '' ?>
                                        >
                                        <label class="form-check-label" for="sms_opt_in">
                                            <?= htmlspecialchars($labels['sms_opt_in'], ENT_QUOTES, 'UTF-8') ?>
                                        </label>
                                        <div class="form-text">
                                            <?= htmlspecialchars($labels['sms_note'], ENT_QUOTES, 'UTF-8') ?>
                                            <?php if ($language === 'es'): ?>
                                                Consulte nuestra
                                                <a href="/privacy" target="_blank" rel="noopener">Política de privacidad</a>
                                                y nuestros
                                                <a href="/terms" target="_blank" rel="noopener">Términos y condiciones</a>.
                                            <?php else: ?>
                                                See our
                                                <a href="/privacy" target="_blank" rel="noopener">Privacy Policy</a>
                                                and
                                                <a href="/terms" target="_blank" rel="noopener">Terms &amp; Conditions</a>.
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </section>

                        <?php if (!empty($questions)): ?>
                            <section class="mb-5">
                                <h2 class="lfchd-section-heading">
                                    <?= htmlspecialchars($labels['additional_info'], ENT_QUOTES, 'UTF-8') ?>
                                </h2>

                                <div class="row g-4">
                                    <?php foreach ($questions as $question): ?>
                                        <?php
                                        $questionId = (int)$question['id'];
                                        $type = (string)$question['question_type'];
                                        $required = !empty($question['required']);
                                        [$englishOptions, $spanishOptions] = questionOptions($question);
                                        $hasOptions = count($englishOptions) > 0;
                                        $answer = $old['answers'][$questionId] ?? '';
                                        ?>
                                        <div
                                            class="col-12 question-wrapper"
                                            data-question-id="<?= $questionId ?>"
                                            <?php if (!empty($question['conditional_question_id'])): ?>
                                                data-condition-question-id="<?= (int)$question['conditional_question_id'] ?>"
                                                data-condition-operator="<?= htmlspecialchars((string)$question['conditional_operator'], ENT_QUOTES, 'UTF-8') ?>"
                                                data-condition-value="<?= htmlspecialchars((string)$question['conditional_value'], ENT_QUOTES, 'UTF-8') ?>"
                                            <?php endif; ?>
                                        >
                                            <label class="form-label">
                                                <?= htmlspecialchars(questionLabel($question, $language), ENT_QUOTES, 'UTF-8') ?>
                                                <?php if ($required): ?>
                                                    <span class="text-danger">*</span>
                                                <?php endif; ?>
                                            </label>

                                            <?php if ($type === 'textarea'): ?>
                                                <textarea
                                                    class="form-control"
                                                    name="answers[<?= $questionId ?>]"
                                                    rows="5"
                                                    <?= $required ? 'data-required="1"' : '' ?>
                                                ><?= htmlspecialchars((string)$answer, ENT_QUOTES, 'UTF-8') ?></textarea>

                                            <?php elseif ($type === 'select'): ?>
                                                <select
                                                    class="form-select"
                                                    name="answers[<?= $questionId ?>]"
                                                    <?= $required ? 'data-required="1"' : '' ?>
                                                >
                                                    <option value="">
                                                        <?= htmlspecialchars($labels['select_one'], ENT_QUOTES, 'UTF-8') ?>
                                                    </option>

                                                    <?php foreach ($englishOptions as $index => $option): ?>
                                                        <?php
                                                        $displayOption = $language === 'es'
                                                            ? ($spanishOptions[$index] ?? $option)
                                                            : $option;
                                                        ?>
                                                        <option
                                                            value="<?= htmlspecialchars((string)$option, ENT_QUOTES, 'UTF-8') ?>"
                                                            <?= (string)$answer === (string)$option ? 'selected' : '' ?>
                                                        >
                                                            <?= htmlspecialchars((string)$displayOption, ENT_QUOTES, 'UTF-8') ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>

                                            <?php elseif ($type === 'checkbox' && $hasOptions): ?>
                                                <?php
                                                $selectedAnswers = [];
                                                if (is_array($answer)) {
                                                    $selectedAnswers = $answer;
                                                } elseif (is_string($answer) && $answer !== '') {
                                                    $decoded = json_decode($answer, true);
                                                    $selectedAnswers = is_array($decoded) ? $decoded : [];
                                                }
                                                ?>
                                                <div class="lfchd-checkbox-group">
                                                    <?php foreach ($englishOptions as $index => $option): ?>
                                                        <?php
                                                        $displayOption = $language === 'es'
                                                            ? ($spanishOptions[$index] ?? $option)
                                                            : $option;
                                                        $optionId = 'question_' . $questionId . '_option_' . $index;
                                                        ?>
                                                        <div class="form-check mb-2">
                                                            <input
                                                                class="form-check-input"
                                                                type="checkbox"
                                                                id="<?= htmlspecialchars($optionId, ENT_QUOTES, 'UTF-8') ?>"
                                                                name="answers[<?= $questionId ?>][]"
                                                                value="<?= htmlspecialchars((string)$option, ENT_QUOTES, 'UTF-8') ?>"
                                                                <?= in_array((string)$option, array_map('strval', $selectedAnswers), true) ? 'checked' : '' ?>
                                                                <?= $required ? 'data-required-group="1"' : '' ?>
                                                            >
                                                            <label class="form-check-label" for="<?= htmlspecialchars($optionId, ENT_QUOTES, 'UTF-8') ?>">
                                                                <?= htmlspecialchars((string)$displayOption, ENT_QUOTES, 'UTF-8') ?>
                                                            </label>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>

                                            <?php elseif ($type === 'checkbox'): ?>
                                                <div class="form-check">
                                                    <input
                                                        class="form-check-input"
                                                        type="checkbox"
                                                        id="question_<?= $questionId ?>"
                                                        name="answers[<?= $questionId ?>]"
                                                        value="1"
                                                        <?= !empty($answer) ? 'checked' : '' ?>
                                                        <?= $required ? 'data-required="1"' : '' ?>
                                                    >
                                                    <label class="form-check-label" for="question_<?= $questionId ?>">
                                                        <?= htmlspecialchars(questionLabel($question, $language), ENT_QUOTES, 'UTF-8') ?>
                                                    </label>
                                                </div>

                                            <?php else: ?>
                                                <input
                                                    class="form-control"
                                                    type="text"
                                                    name="answers[<?= $questionId ?>]"
                                                    value="<?= htmlspecialchars((string)$answer, ENT_QUOTES, 'UTF-8') ?>"
                                                    <?= $required ? 'data-required="1"' : '' ?>
                                                >
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </section>
                        <?php endif; ?>

                        <div class="d-grid d-sm-flex justify-content-sm-end">
                            <button type="submit" class="btn btn-primary btn-lg lfchd-submit-button">
                                <?= htmlspecialchars($labels['submit'], ENT_QUOTES, 'UTF-8') ?>
                            </button>
                        </div>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
</main>

<footer class="lfchd-footer">
    <div class="container py-4">
        <div class="text-center small">
            Lexington-Fayette County Health Department
            <span class="mx-1">·</span>
            <a href="/privacy">
                <?= $language === 'es' ? 'Política de privacidad' : 'Privacy Policy' ?>
            </a>
            <span class="mx-1">·</span>
            <a href="/terms">
                <?= $language === 'es' ? 'Términos y condiciones' : 'Terms &amp; Conditions' ?>
            </a>
        </div>
    </div>
</footer>

<script>
(function () {
    const form = document.getElementById('registration-form');
    if (!form) return;

    const wrappers = Array.from(form.querySelectorAll('.question-wrapper'));

    function valuesForQuestion(questionId) {
        const wrapper = form.querySelector('.question-wrapper[data-question-id="' + questionId + '"]');
        if (!wrapper || wrapper.hidden) return [];

        const controls = Array.from(wrapper.querySelectorAll('input, select, textarea'));
        const checkboxes = controls.filter(control => control.type === 'checkbox');

        if (checkboxes.length) {
            return checkboxes.filter(control => control.checked).map(control => control.value);
        }

        const control = controls[0];
        return control && control.value !== '' ? [control.value] : [];
    }

    function clearWrapper(wrapper) {
        wrapper.querySelectorAll('input, select, textarea').forEach(control => {
            if (control.type === 'checkbox' || control.type === 'radio') {
                control.checked = false;
            } else {
                control.value = '';
            }
        });
    }

    function applyRequiredState(wrapper, visible) {
        wrapper.querySelectorAll('[data-required="1"]').forEach(control => {
            control.required = visible;
        });

        const requiredGroup = Array.from(wrapper.querySelectorAll('[data-required-group="1"]'));
        requiredGroup.forEach(control => control.required = false);

        if (visible && requiredGroup.length && !requiredGroup.some(control => control.checked)) {
            requiredGroup[0].required = true;
        }
    }

    function updateConditionalQuestions() {
        wrappers.forEach(wrapper => {
            const controllingId = wrapper.dataset.conditionQuestionId;

            if (!controllingId) {
                wrapper.hidden = false;
                applyRequiredState(wrapper, true);
                return;
            }

            const actualValues = valuesForQuestion(controllingId);
            const expected = wrapper.dataset.conditionValue || '';
            const operator = wrapper.dataset.conditionOperator || 'equals';

            let visible = actualValues.includes(expected);
            if (operator === 'not_equals') {
                visible = !visible;
            }

            if (!visible && !wrapper.hidden) {
                clearWrapper(wrapper);
            }

            wrapper.hidden = !visible;
            applyRequiredState(wrapper, visible);
        });
    }

    form.addEventListener('change', updateConditionalQuestions);
    form.addEventListener('input', updateConditionalQuestions);
    updateConditionalQuestions();
})();
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
