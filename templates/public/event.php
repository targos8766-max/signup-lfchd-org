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
        <?= htmlspecialchars(
            $event['title'],
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

                    <h1 class="mb-3">
                        <?= htmlspecialchars(
                            $event['title'],
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </h1>

                    <?php if (!empty($event['description'])): ?>

                        <p>
                            <?= nl2br(
                                htmlspecialchars(
                                    $event['description'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                )
                            ) ?>
                        </p>

                    <?php endif; ?>

                    <dl class="row mb-0">

                        <dt class="col-sm-3">
                            Date
                        </dt>

                        <dd class="col-sm-9">
                            <?= (
                                new DateTimeImmutable(
                                    $event['event_date']
                                )
                            )->format('l, F j, Y') ?>
                        </dd>

                        <?php if (!empty($event['location'])): ?>

                            <dt class="col-sm-3">
                                Location
                            </dt>

                            <dd class="col-sm-9">
                                <?= htmlspecialchars(
                                    $event['location'],
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
                        ?? 'Registration is unavailable.',
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

                    <div class="card shadow-sm mb-4">

                        <div class="card-header">
                            <strong>
                                Select a Time
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
                                    There are currently no available
                                    appointment times.
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
                                                            seat<?= $remaining === 1
                                                                ? ''
                                                                : 's' ?>
                                                            remaining

                                                        <?php else: ?>

                                                            Full

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
                                    Your Information
                                </strong>
                            </div>

                            <div class="card-body">

                                <div class="row">

                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">
                                            First Name
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
                                            Last Name
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
                                        Email
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
                                        Phone
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
                                            Send me registration confirmation
                                            and reminder text messages from LFCHD.
                                        </label>
                                    </div>

                                    <div class="form-text ms-4">
                                        Message frequency varies. Message and
                                        data rates may apply. Reply STOP to
                                        unsubscribe. SMS consent is optional
                                        and is not required to register.
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">
                                        Department
                                    </label>

                                    <input
                                        type="text"
                                        name="department"
                                        class="form-control"
                                        value="<?= htmlspecialchars(
                                            $old['department'] ?? '',
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>"
                                    >
                                </div>

                                <?php if (!empty($questions)): ?>

                                    <hr class="my-4">

                                    <h2 class="h5 mb-3">
                                        Additional Information
                                    </h2>

                                    <?php foreach ($questions as $question): ?>

                                        <?php
                                        $questionId = (int) $question['id'];

                                        $oldAnswer =
                                            $old['answers'][$questionId]
                                            ?? '';

                                        if (is_array($oldAnswer)) {
                                            $oldAnswer = '';
                                        }

                                        $oldAnswer =
                                            (string) $oldAnswer;

                                        $conditionalQuestionId =
                                            $question['conditional_question_id'] ?? null;

                                        $conditionalOperator =
                                            $question['conditional_operator'] ?? null;

                                        $conditionalValue =
                                            $question['conditional_value'] ?? null;
                                        ?>

                                        <div
                                            class="mb-3 conditional-question"
                                            data-question-id="<?= $questionId ?>"
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
                                                        $question['question_text'],
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
                                                        $question['question_text'],
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
                                                    rows="4"
                                                    <?= (int) $question['required'] === 1
                                                        ? 'required'
                                                        : '' ?>
                                                ><?= htmlspecialchars(
                                                    $oldAnswer,
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                ) ?></textarea>

                                            <?php elseif ($question['question_type'] === 'select'): ?>

                                                <?php
                                                $options = json_decode(
                                                    $question['options_json'] ?? '[]',
                                                    true
                                                );

                                                if (!is_array($options)) {
                                                    $options = [];
                                                }
                                                ?>

                                                <label
                                                    for="question-<?= $questionId ?>"
                                                    class="form-label"
                                                >
                                                    <?= htmlspecialchars(
                                                        $question['question_text'],
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
                                                        Select an option
                                                    </option>

                                                    <?php foreach ($options as $option): ?>

                                                        <option
                                                            value="<?= htmlspecialchars(
                                                                $option,
                                                                ENT_QUOTES,
                                                                'UTF-8'
                                                            ) ?>"
                                                            <?= $oldAnswer === $option
                                                                ? 'selected'
                                                                : '' ?>
                                                        >
                                                            <?= htmlspecialchars(
                                                                $option,
                                                                ENT_QUOTES,
                                                                'UTF-8'
                                                            ) ?>
                                                        </option>

                                                    <?php endforeach; ?>

                                                </select>

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
                                                            $question['question_text'],
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
                                    Complete Signup
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

    function updateSmsPhoneRequirement() {
        if (!smsOptIn || !phone) {
            return;
        }

        phone.required = smsOptIn.checked;
    }

    function getQuestionValue(questionId) {
        const checkbox = document.querySelector(
            '[name="answers[' + questionId + ']"][type="checkbox"]'
        );

        if (checkbox) {
            return checkbox.checked ? '1' : '0';
        }

        const field = document.querySelector(
            '[name="answers[' + questionId + ']"]'
        );

        if (!field) {
            return '';
        }

        return field.value;
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

            let shouldShow = false;

            if (operator === 'equals') {
                shouldShow =
                    actualValue === expectedValue;
            } else if (operator === 'not_equals') {
                shouldShow =
                    actualValue !== expectedValue;
            }

            if (shouldShow) {
                container.style.display = '';
                setRequiredState(container, true);
            } else {
                container.style.display = 'none';
                clearQuestionValue(container);
                setRequiredState(container, false);
            }
        });
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

    updateConditionalQuestions();
    updateSmsPhoneRequirement();
});
</script>

</body>
</html>
