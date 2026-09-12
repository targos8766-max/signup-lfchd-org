<?php

use Boneblaze\SignupLfchdOrg\Services\Csrf;

$language = $language ?? ($old['preferred_language'] ?? 'en');
$language = $language === 'es' ? 'es' : 'en';

$translations = [
    'en' => [
        'preferred_language' => 'Preferred Language',
        'english' => 'English',
        'spanish' => 'Español',
        'date' => 'Date',
        'location' => 'Location',
        'select_time' => 'Select a Time',
        'no_times' => 'There are currently no available appointment times.',
        'seat' => 'seat',
        'seats' => 'seats',
        'remaining' => 'remaining',
        'full' => 'Full',
        'your_information' => 'Your Information',
        'first_name' => 'First Name',
        'last_name' => 'Last Name',
        'email' => 'Email',
        'phone' => 'Phone',
        'sms_label' => 'Send me registration confirmation and reminder text messages from LFCHD.',
        'sms_help' => 'Message frequency varies. Message and data rates may apply. Reply STOP to unsubscribe. SMS consent is optional and is not required to register.',
        'additional_information' => 'Additional Information',
        'select_option' => 'Select an option',
        'complete_signup' => 'Complete Signup',
        'registration_unavailable' => 'Registration is unavailable.',
    ],
    'es' => [
        'preferred_language' => 'Idioma preferido',
        'english' => 'English',
        'spanish' => 'Español',
        'date' => 'Fecha',
        'location' => 'Ubicación',
        'select_time' => 'Seleccione una hora',
        'no_times' => 'Actualmente no hay horarios disponibles.',
        'seat' => 'cupo',
        'seats' => 'cupos',
        'remaining' => 'disponibles',
        'full' => 'Lleno',
        'your_information' => 'Su información',
        'first_name' => 'Nombre',
        'last_name' => 'Apellido',
        'email' => 'Correo electrónico',
        'phone' => 'Teléfono',
        'sms_label' => 'Envíeme por mensaje de texto la confirmación de registro y recordatorios de LFCHD.',
        'sms_help' => 'La frecuencia de los mensajes varía. Pueden aplicarse tarifas de mensajes y datos. Responda STOP para dejar de recibir mensajes. El consentimiento para SMS es opcional y no es necesario para registrarse.',
        'additional_information' => 'Información adicional',
        'select_option' => 'Seleccione una opción',
        'complete_signup' => 'Completar registro',
        'registration_unavailable' => 'El registro no está disponible.',
    ],
];

$t = $translations[$language];

$eventTitle = $language === 'es' && !empty($event['title_es'])
    ? $event['title_es']
    : $event['title'];

$eventDescription = $language === 'es' && !empty($event['description_es'])
    ? $event['description_es']
    : ($event['description'] ?? '');

$eventLocation = $language === 'es' && !empty($event['location_es'])
    ? $event['location_es']
    : ($event['location'] ?? '');

function publicEventDate(string $date, string $language): string
{
    $value = new DateTimeImmutable($date);

    if ($language !== 'es') {
        return $value->format('l, F j, Y');
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

    return $days[$value->format('l')]
        . ', '
        . $value->format('j')
        . ' de '
        . $months[$value->format('F')]
        . ' de '
        . $value->format('Y');
}

function questionLabel(array $question, string $language): string
{
    if (
        $language === 'es'
        && !empty($question['question_text_es'])
    ) {
        return $question['question_text_es'];
    }

    return $question['question_text'];
}

function questionOptions(array $question, string $language): array
{
    $englishOptions = json_decode(
        $question['options_json'] ?? '[]',
        true
    );

    if (!is_array($englishOptions)) {
        $englishOptions = [];
    }

    $displayOptions = $englishOptions;

    if (
        $language === 'es'
        && !empty($question['options_json_es'])
    ) {
        $spanishOptions = json_decode(
            $question['options_json_es'],
            true
        );

        if (
            is_array($spanishOptions)
            && count($spanishOptions) === count($englishOptions)
        ) {
            $displayOptions = $spanishOptions;
        }
    }

    $result = [];

    foreach ($englishOptions as $index => $value) {
        $result[] = [
            'value' => (string) $value,
            'label' => (string) ($displayOptions[$index] ?? $value),
        ];
    }

    return $result;
}

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
            $eventTitle,
            ENT_QUOTES,
            'UTF-8'
        ) ?>
    </title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <style>
        .slot-option input {
            position: absolute;
            opacity: 0;
            pointer-events: none;
        }

        .slot-option label {
            cursor: pointer;
            width: 100%;
        }

        .slot-option input:checked + label {
            border-color: var(--bs-primary);
            background-color: var(--bs-primary-bg-subtle);
        }

        .slot-full label {
            cursor: not-allowed;
            opacity: .6;
        }
    </style>
</head>

<body class="bg-light">

<div class="container py-5">

    <div class="row justify-content-center">
        <div class="col-lg-8">

            <div class="card shadow-sm mb-4">
                <div class="card-body">

                    <div class="d-flex justify-content-end mb-3">
                        <div>
                            <label
                                for="preferred_language_selector"
                                class="form-label fw-semibold mb-1"
                            >
                                Preferred Language / Idioma preferido
                            </label>

                            <select
                                id="preferred_language_selector"
                                class="form-select"
                                style="min-width: 180px;"
                            >
                                <option
                                    value="en"
                                    <?= $language === 'en' ? 'selected' : '' ?>
                                >
                                    English
                                </option>

                                <option
                                    value="es"
                                    <?= $language === 'es' ? 'selected' : '' ?>
                                >
                                    Español
                                </option>
                            </select>
                        </div>
                    </div>

                    <h1 class="mb-3">
                        <?= htmlspecialchars(
                            $eventTitle,
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </h1>

                    <?php if ($eventDescription !== ''): ?>

                        <p>
                            <?= nl2br(
                                htmlspecialchars(
                                    $eventDescription,
                                    ENT_QUOTES,
                                    'UTF-8'
                                )
                            ) ?>
                        </p>

                    <?php endif; ?>

                    <dl class="row mb-0">

                        <dt class="col-sm-3">
                            <?= htmlspecialchars($t['date']) ?>
                        </dt>

                        <dd class="col-sm-9">
                            <?= htmlspecialchars(
                                publicEventDate(
                                    $event['event_date'],
                                    $language
                                ),
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </dd>

                        <?php if ($eventLocation !== ''): ?>

                            <dt class="col-sm-3">
                                <?= htmlspecialchars($t['location']) ?>
                            </dt>

                            <dd class="col-sm-9">
                                <?= htmlspecialchars(
                                    $eventLocation,
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </dd>

                        <?php endif; ?>

                    </dl>

                </div>
            </div>

            <?php if (!empty($errors)): ?>

                <div class="alert alert-danger">
                    <ul class="mb-0">

                        <?php foreach ($errors as $error): ?>

                            <li>
                                <?= htmlspecialchars(
                                    $error,
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </li>

                        <?php endforeach; ?>

                    </ul>
                </div>

            <?php endif; ?>

            <?php if (!$registrationOpen): ?>

                <div class="alert alert-info">
                    <?= htmlspecialchars(
                        $registrationMessage
                        ?? $t['registration_unavailable'],
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </div>

            <?php else: ?>

                <form
                    method="post"
                    action="/event/<?= rawurlencode(
                        $event['public_slug']
                    ) ?>/register"
                >

                    <?= Csrf::field() ?>

                    <input
                        type="hidden"
                        id="preferred_language"
                        name="preferred_language"
                        value="<?= $language ?>"
                    >

                    <div class="card shadow-sm mb-4">

                        <div class="card-header">
                            <strong>
                                <?= htmlspecialchars($t['select_time']) ?>
                            </strong>
                        </div>

                        <div class="card-body">

                            <?php
                            $availableSlots = array_filter(
                                $slots,
                                fn ($slot) =>
                                    (int) $slot['remaining'] > 0
                            );
                            ?>

                            <?php if (!$availableSlots): ?>

                                <div class="alert alert-warning mb-0">
                                    <?= htmlspecialchars($t['no_times']) ?>
                                </div>

                            <?php else: ?>

                                <div class="row g-3">

                                    <?php foreach ($slots as $slot): ?>

                                        <?php
                                        $start = new DateTimeImmutable(
                                            $slot['start_datetime']
                                        );

                                        $end = new DateTimeImmutable(
                                            $slot['end_datetime']
                                        );

                                        $remaining =
                                            (int) $slot['remaining'];

                                        $slotId =
                                            (int) $slot['id'];

                                        $selected =
                                            isset($old['slot_id'])
                                            && (int) $old['slot_id']
                                                === $slotId;
                                        ?>

                                        <div class="col-md-6">

                                            <div
                                                class="slot-option
                                                <?= $remaining < 1
                                                    ? 'slot-full'
                                                    : '' ?>"
                                            >

                                                <input
                                                    type="radio"
                                                    id="slot-<?= $slotId ?>"
                                                    name="slot_id"
                                                    value="<?= $slotId ?>"
                                                    <?= $remaining < 1
                                                        ? 'disabled'
                                                        : '' ?>
                                                    <?= $selected
                                                        ? 'checked'
                                                        : '' ?>
                                                >

                                                <label
                                                    for="slot-<?= $slotId ?>"
                                                    class="card p-3"
                                                >

                                                    <strong>
                                                        <?= $start->format(
                                                            'g:i A'
                                                        ) ?>
                                                        –
                                                        <?= $end->format(
                                                            'g:i A'
                                                        ) ?>
                                                    </strong>

                                                    <span class="small text-muted">

                                                        <?php if ($remaining > 0): ?>

                                                            <?= $remaining ?>
                                                            <?= htmlspecialchars(
                                                                $remaining === 1
                                                                    ? $t['seat']
                                                                    : $t['seats']
                                                            ) ?>
                                                            <?= htmlspecialchars($t['remaining']) ?>

                                                        <?php else: ?>

                                                            <?= htmlspecialchars($t['full']) ?>

                                                        <?php endif; ?>

                                                    </span>

                                                </label>

                                            </div>

                                        </div>

                                    <?php endforeach; ?>

                                </div>

                            <?php endif; ?>

                        </div>
                    </div>

                    <?php if ($availableSlots): ?>

                        <div class="card shadow-sm">

                            <div class="card-header">
                                <strong>
                                    <?= htmlspecialchars($t['your_information']) ?>
                                </strong>
                            </div>

                            <div class="card-body">

                                <div class="row">

                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">
                                            <?= htmlspecialchars($t['first_name']) ?>
                                        </label>

                                        <input
                                            type="text"
                                            name="first_name"
                                            class="form-control"
                                            required
                                            value="<?= htmlspecialchars(
                                                $old['first_name'] ?? '',
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>"
                                        >
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">
                                            <?= htmlspecialchars($t['last_name']) ?>
                                        </label>

                                        <input
                                            type="text"
                                            name="last_name"
                                            class="form-control"
                                            required
                                            value="<?= htmlspecialchars(
                                                $old['last_name'] ?? '',
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>"
                                        >
                                    </div>

                                </div>

                                <div class="mb-3">
                                    <label class="form-label">
                                        <?= htmlspecialchars($t['email']) ?>
                                    </label>

                                    <input
                                        type="email"
                                        name="email"
                                        class="form-control"
                                        value="<?= htmlspecialchars(
                                            $old['email'] ?? '',
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>"
                                    >
                                </div>

                                <div class="mb-3">
                                    <label
                                        for="phone"
                                        class="form-label"
                                    >
                                        <?= htmlspecialchars($t['phone']) ?>
                                    </label>

                                    <input
                                        type="tel"
                                        id="phone"
                                        name="phone"
                                        class="form-control"
                                        autocomplete="tel"
                                        value="<?= htmlspecialchars(
                                            $old['phone'] ?? '',
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>"
                                    >
                                </div>

                                <div class="mb-4">
                                    <div class="form-check">
                                        <input
                                            type="checkbox"
                                            id="sms_opt_in"
                                            name="sms_opt_in"
                                            value="1"
                                            class="form-check-input"
                                            <?= (
                                                isset($old['sms_opt_in'])
                                                && (string) $old['sms_opt_in'] === '1'
                                            ) ? 'checked' : '' ?>
                                        >

                                        <label
                                            for="sms_opt_in"
                                            class="form-check-label"
                                        >
                                            <?= htmlspecialchars($t['sms_label']) ?>
                                        </label>
                                    </div>

                                    <div class="form-text ms-4">
                                        <?= htmlspecialchars($t['sms_help']) ?>
                                    </div>
                                </div>

                                <?php if (!empty($questions)): ?>

                                    <hr class="my-4">

                                    <h2 class="h5 mb-3">
                                        <?= htmlspecialchars($t['additional_information']) ?>
                                    </h2>

                                    <?php foreach ($questions as $question): ?>

                                        <?php
                                        $questionId = (int) $question['id'];

                                        $oldAnswer =
                                            $old['answers'][$questionId]
                                            ?? '';

                                        $oldAnswers = [];

                                        if (is_array($oldAnswer)) {
                                            $oldAnswers = array_map(
                                                'strval',
                                                $oldAnswer
                                            );
                                        } else {
                                            $oldAnswer = (string) $oldAnswer;
                                        }

                                        $conditionalQuestionId =
                                            $question['conditional_question_id'] ?? null;

                                        $conditionalOperator =
                                            $question['conditional_operator'] ?? null;

                                        $conditionalValue =
                                            $question['conditional_value'] ?? null;

                                        $label = questionLabel(
                                            $question,
                                            $language
                                        );

                                        $options = questionOptions(
                                            $question,
                                            $language
                                        );

                                        $isMultiCheckbox =
                                            $question['question_type'] === 'checkbox'
                                            && $options !== [];
                                        ?>

                                        <div
                                            class="mb-3 conditional-question"
                                            data-question-id="<?= $questionId ?>"
                                            data-question-type="<?= htmlspecialchars(
                                                $question['question_type'],
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>"
                                            data-condition-question-id="<?= htmlspecialchars(
                                                (string) ($conditionalQuestionId ?? ''),
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>"
                                            data-condition-operator="<?= htmlspecialchars(
                                                (string) ($conditionalOperator ?? ''),
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>"
                                            data-condition-value="<?= htmlspecialchars(
                                                (string) ($conditionalValue ?? ''),
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>"
                                        >

                                            <?php if ($question['question_type'] === 'text'): ?>

                                                <label
                                                    for="question-<?= $questionId ?>"
                                                    class="form-label"
                                                >
                                                    <?= htmlspecialchars(
                                                        $label,
                                                        ENT_QUOTES,
                                                        'UTF-8'
                                                    ) ?>

                                                    <?php if ((int) $question['required'] === 1): ?>
                                                        <span class="text-danger">*</span>
                                                    <?php endif; ?>
                                                </label>

                                                <input
                                                    type="text"
                                                    id="question-<?= $questionId ?>"
                                                    name="answers[<?= $questionId ?>]"
                                                    class="form-control"
                                                    value="<?= htmlspecialchars(
                                                        $oldAnswer,
                                                        ENT_QUOTES,
                                                        'UTF-8'
                                                    ) ?>"
                                                    <?= (int) $question['required'] === 1
                                                        ? 'required'
                                                        : '' ?>
                                                >

                                            <?php elseif ($question['question_type'] === 'textarea'): ?>

                                                <label
                                                    for="question-<?= $questionId ?>"
                                                    class="form-label"
                                                >
                                                    <?= htmlspecialchars(
                                                        $label,
                                                        ENT_QUOTES,
                                                        'UTF-8'
                                                    ) ?>

                                                    <?php if ((int) $question['required'] === 1): ?>
                                                        <span class="text-danger">*</span>
                                                    <?php endif; ?>
                                                </label>

                                                <textarea
                                                    id="question-<?= $questionId ?>"
                                                    name="answers[<?= $questionId ?>]"
                                                    class="form-control"
                                                    rows="5"
                                                    <?= (int) $question['required'] === 1
                                                        ? 'required'
                                                        : '' ?>
                                                ><?= htmlspecialchars(
                                                    $oldAnswer,
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                ) ?></textarea>

                                            <?php elseif ($question['question_type'] === 'select'): ?>

                                                <label
                                                    for="question-<?= $questionId ?>"
                                                    class="form-label"
                                                >
                                                    <?= htmlspecialchars(
                                                        $label,
                                                        ENT_QUOTES,
                                                        'UTF-8'
                                                    ) ?>

                                                    <?php if ((int) $question['required'] === 1): ?>
                                                        <span class="text-danger">*</span>
                                                    <?php endif; ?>
                                                </label>

                                                <select
                                                    id="question-<?= $questionId ?>"
                                                    name="answers[<?= $questionId ?>]"
                                                    class="form-select"
                                                    <?= (int) $question['required'] === 1
                                                        ? 'required'
                                                        : '' ?>
                                                >

                                                    <option value="">
                                                        <?= htmlspecialchars($t['select_option']) ?>
                                                    </option>

                                                    <?php foreach ($options as $option): ?>

                                                        <option
                                                            value="<?= htmlspecialchars(
                                                                $option['value'],
                                                                ENT_QUOTES,
                                                                'UTF-8'
                                                            ) ?>"
                                                            <?= $oldAnswer === $option['value']
                                                                ? 'selected'
                                                                : '' ?>
                                                        >
                                                            <?= htmlspecialchars(
                                                                $option['label'],
                                                                ENT_QUOTES,
                                                                'UTF-8'
                                                            ) ?>
                                                        </option>

                                                    <?php endforeach; ?>

                                                </select>

                                            <?php elseif (
                                                $question['question_type'] === 'checkbox'
                                                && $isMultiCheckbox
                                            ): ?>

                                                <div class="form-label">
                                                    <?= htmlspecialchars(
                                                        $label,
                                                        ENT_QUOTES,
                                                        'UTF-8'
                                                    ) ?>

                                                    <?php if ((int) $question['required'] === 1): ?>
                                                        <span class="text-danger">*</span>
                                                    <?php endif; ?>
                                                </div>

                                                <?php foreach ($options as $index => $option): ?>

                                                    <?php
                                                    $checkboxId =
                                                        'question-'
                                                        . $questionId
                                                        . '-'
                                                        . $index;
                                                    ?>

                                                    <div class="form-check mb-2">

                                                        <input
                                                            type="checkbox"
                                                            id="<?= $checkboxId ?>"
                                                            name="answers[<?= $questionId ?>][]"
                                                            value="<?= htmlspecialchars(
                                                                $option['value'],
                                                                ENT_QUOTES,
                                                                'UTF-8'
                                                            ) ?>"
                                                            class="form-check-input multi-checkbox-answer"
                                                            <?= in_array(
                                                                $option['value'],
                                                                $oldAnswers,
                                                                true
                                                            )
                                                                ? 'checked'
                                                                : '' ?>
                                                            <?= (int) $question['required'] === 1
                                                                ? 'data-checkbox-group-required="1"'
                                                                : '' ?>
                                                        >

                                                        <label
                                                            for="<?= $checkboxId ?>"
                                                            class="form-check-label"
                                                        >
                                                            <?= htmlspecialchars(
                                                                $option['label'],
                                                                ENT_QUOTES,
                                                                'UTF-8'
                                                            ) ?>
                                                        </label>

                                                    </div>

                                                <?php endforeach; ?>

                                            <?php elseif ($question['question_type'] === 'checkbox'): ?>

                                                <div class="form-check">

                                                    <input
                                                        type="checkbox"
                                                        id="question-<?= $questionId ?>"
                                                        name="answers[<?= $questionId ?>]"
                                                        value="1"
                                                        class="form-check-input"
                                                        <?= $oldAnswer === '1'
                                                            ? 'checked'
                                                            : '' ?>
                                                        <?= (int) $question['required'] === 1
                                                            ? 'required'
                                                            : '' ?>
                                                    >

                                                    <label
                                                        for="question-<?= $questionId ?>"
                                                        class="form-check-label"
                                                    >
                                                        <?= htmlspecialchars(
                                                            $label,
                                                            ENT_QUOTES,
                                                            'UTF-8'
                                                        ) ?>

                                                        <?php if ((int) $question['required'] === 1): ?>
                                                            <span class="text-danger">*</span>
                                                        <?php endif; ?>
                                                    </label>

                                                </div>

                                            <?php endif; ?>

                                        </div>

                                    <?php endforeach; ?>

                                <?php endif; ?>

                            </div>

                            <div class="card-footer text-end">

                                <button
                                    type="submit"
                                    class="btn btn-primary btn-lg"
                                >
                                    <?= htmlspecialchars($t['complete_signup']) ?>
                                </button>

                            </div>

                        </div>

                    <?php endif; ?>

                </form>

            <?php endif; ?>

        </div>
    </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const questionContainers =
        document.querySelectorAll('.conditional-question');

    const smsOptIn =
        document.getElementById('sms_opt_in');

    const phone =
        document.getElementById('phone');

    const languageSelector =
        document.getElementById('preferred_language_selector');

    const languageInput =
        document.getElementById('preferred_language');

    function updateSmsPhoneRequirement() {
        if (!smsOptIn || !phone) {
            return;
        }

        phone.required = smsOptIn.checked;
    }

    function getQuestionValue(questionId) {
        const fields = document.querySelectorAll(
            '[name="answers[' + questionId + ']"], '
            + '[name="answers[' + questionId + '][]"]'
        );

        if (!fields.length) {
            return '';
        }

        if (
            fields.length > 1
            || fields[0].name.endsWith('[]')
        ) {
            const checkedValues = Array.from(fields)
                .filter(function (field) {
                    return field.type === 'checkbox'
                        && field.checked;
                })
                .map(function (field) {
                    return field.value;
                });

            return checkedValues;
        }

        const field = fields[0];

        if (field.type === 'checkbox') {
            return field.checked ? '1' : '0';
        }

        return field.value;
    }

    function conditionMatches(
        actualValue,
        operator,
        expectedValue
    ) {
        if (Array.isArray(actualValue)) {
            const contains =
                actualValue.includes(expectedValue);

            return operator === 'not_equals'
                ? !contains
                : contains;
        }

        if (operator === 'equals') {
            return actualValue === expectedValue;
        }

        if (operator === 'not_equals') {
            return actualValue !== expectedValue;
        }

        return true;
    }

    function updateConditionalQuestions() {
        questionContainers.forEach(function (container) {
            const controllingQuestionId =
                container.dataset.conditionQuestionId;

            const operator =
                container.dataset.conditionOperator;

            const expectedValue =
                container.dataset.conditionValue;

            if (!controllingQuestionId) {
                container.style.display = '';
                setRequiredState(container, true);
                return;
            }

            const actualValue =
                getQuestionValue(controllingQuestionId);

            const shouldShow =
                conditionMatches(
                    actualValue,
                    operator,
                    expectedValue
                );

            if (shouldShow) {
                container.style.display = '';
                setRequiredState(container, true);
            } else {
                container.style.display = 'none';
                clearQuestionValue(container);
                setRequiredState(container, false);
            }
        });

        updateMultiCheckboxRequirements();
    }

    function clearQuestionValue(container) {
        const fields =
            container.querySelectorAll(
                'input, select, textarea'
            );

        fields.forEach(function (field) {
            if (field.type === 'checkbox') {
                field.checked = false;
            } else {
                field.value = '';
            }
        });
    }

    function setRequiredState(container, enabled) {
        const requiredFields =
            container.querySelectorAll(
                '[data-conditionally-required="1"]'
            );

        requiredFields.forEach(function (field) {
            field.required = enabled;
        });

        container.dataset.conditionActive =
            enabled ? '1' : '0';
    }

    function updateMultiCheckboxRequirements() {
        questionContainers.forEach(function (container) {
            const requiredCheckboxes =
                container.querySelectorAll(
                    '[data-checkbox-group-required="1"]'
                );

            if (!requiredCheckboxes.length) {
                return;
            }

            const active =
                container.dataset.conditionActive !== '0';

            const anyChecked =
                Array.from(requiredCheckboxes)
                    .some(function (field) {
                        return field.checked;
                    });

            requiredCheckboxes.forEach(function (field, index) {
                field.required =
                    active
                    && !anyChecked
                    && index === 0;
            });
        });
    }

    questionContainers.forEach(function (container) {
        const fields =
            container.querySelectorAll(
                'input[required], select[required], textarea[required]'
            );

        fields.forEach(function (field) {
            field.dataset.conditionallyRequired = '1';
        });
    });

    document.addEventListener('change', function (event) {
        if (
            event.target.name
            && event.target.name.startsWith('answers[')
        ) {
            updateConditionalQuestions();
        }

        if (event.target.id === 'sms_opt_in') {
            updateSmsPhoneRequirement();
        }
    });

    if (languageSelector) {
        languageSelector.addEventListener(
            'change',
            function () {
                if (languageInput) {
                    languageInput.value =
                        languageSelector.value;
                }

                const url = new URL(window.location.href);
                url.searchParams.set(
                    'lang',
                    languageSelector.value
                );

                window.location.href = url.toString();
            }
        );
    }

    updateConditionalQuestions();
    updateSmsPhoneRequirement();
});
</script>

</body>
</html>
