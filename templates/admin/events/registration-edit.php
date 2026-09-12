<?php

use Boneblaze\SignupLfchdOrg\Services\Csrf;

$pageTitle = 'Edit Registration | LFCHD Signup';

require dirname(__DIR__) . '/partials/header.php';
?>

<div class="container py-5">

    <div class="row justify-content-center">
        <div class="col-lg-9">

            <h1 class="mb-1">
                Edit Registration
            </h1>

            <div class="text-muted mb-4">
                <?= htmlspecialchars(
                    $registration['event_title'],
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>
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

            <?php
            $start = new DateTimeImmutable(
                $registration['start_datetime']
            );

            $end = new DateTimeImmutable(
                $registration['end_datetime']
            );
            ?>

            <div class="card shadow-sm mb-4">

                <div class="card-body">

                    <div class="text-muted small">
                        Registration Time
                    </div>

                    <div class="fw-semibold">
                        <?= $start->format(
                            'l, F j, Y'
                        ) ?>
                        <br>
                        <?= $start->format('g:i A') ?>
                        –
                        <?= $end->format('g:i A') ?>
                    </div>

                </div>

            </div>

            <form
                method="post"
                action="/admin/events/<?= (int) $registration['event_id'] ?>/registrations/<?= (int) $registration['id'] ?>/edit"
                class="card shadow-sm"
            >

                <?= Csrf::field() ?>

                <div class="card-body">

                    <h2 class="h5 mb-3">
                        Registration Information
                    </h2>

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
                                    $registration['first_name'],
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
                                    $registration['last_name'],
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
                                $registration['email'] ?? '',
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>"
                        >

                    </div>

                    <div class="mb-3">

                        <label class="form-label">
                            Phone
                        </label>

                        <input
                            type="tel"
                            name="phone"
                            class="form-control"
                            value="<?= htmlspecialchars(
                                $registration['phone'] ?? '',
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>"
                        >

                    </div>

                    <?php if (!empty($questions)): ?>

                        <hr class="my-4">

                        <h2 class="h5 mb-3">
                            Custom Questions
                        </h2>

                        <?php foreach ($questions as $question): ?>

                            <?php
                            $questionId =
                                (int) $question['id'];

                            $answer =
                                $answers[$questionId]
                                ?? '';

                            if (is_array($answer)) {
                                $answer = '';
                            }

                            $answer = (string) $answer;

                            $conditionalQuestionId =
                                $question['conditional_question_id']
                                ?? null;

                            $conditionalOperator =
                                $question['conditional_operator']
                                ?? null;

                            $conditionalValue =
                                $question['conditional_value']
                                ?? null;
                            ?>

                            <div
                                class="mb-4 conditional-question"
                                data-question-id="<?= $questionId ?>"
                                data-condition-question-id="<?= htmlspecialchars(
                                    (string) (
                                        $conditionalQuestionId
                                        ?? ''
                                    ),
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>"
                                data-condition-operator="<?= htmlspecialchars(
                                    (string) (
                                        $conditionalOperator
                                        ?? ''
                                    ),
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>"
                                data-condition-value="<?= htmlspecialchars(
                                    (string) (
                                        $conditionalValue
                                        ?? ''
                                    ),
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>"
                            >

                                <label class="form-label fw-semibold">
                                    <?= htmlspecialchars(
                                        $question['question_text'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>

                                    <?php if ((int) $question['required'] === 1): ?>
                                        <span class="text-danger">*</span>
                                    <?php endif; ?>
                                </label>

                                <?php if ($question['question_type'] === 'text'): ?>

                                    <input
                                        type="text"
                                        name="answers[<?= $questionId ?>]"
                                        class="form-control"
                                        value="<?= htmlspecialchars(
                                            $answer,
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>"
                                        <?= (int) $question['required'] === 1
                                            ? 'required data-conditionally-required="1"'
                                            : '' ?>
                                    >

                                <?php elseif ($question['question_type'] === 'textarea'): ?>

                                    <textarea
                                        name="answers[<?= $questionId ?>]"
                                        class="form-control"
                                        rows="4"
                                        <?= (int) $question['required'] === 1
                                            ? 'required data-conditionally-required="1"'
                                            : '' ?>
                                    ><?= htmlspecialchars(
                                        $answer,
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?></textarea>

                                <?php elseif ($question['question_type'] === 'select'): ?>

                                    <?php
                                    $options = json_decode(
                                        $question['options_json']
                                            ?? '[]',
                                        true
                                    );

                                    if (!is_array($options)) {
                                        $options = [];
                                    }
                                    ?>

                                    <select
                                        name="answers[<?= $questionId ?>]"
                                        class="form-select"
                                        <?= (int) $question['required'] === 1
                                            ? 'required data-conditionally-required="1"'
                                            : '' ?>
                                    >
                                        <option value="">
                                            Select an option
                                        </option>

                                        <?php foreach ($options as $option): ?>

                                            <option
                                                value="<?= htmlspecialchars(
                                                    (string) $option,
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                ) ?>"
                                                <?= $answer === (string) $option
                                                    ? 'selected'
                                                    : '' ?>
                                            >
                                                <?= htmlspecialchars(
                                                    (string) $option,
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
                                            name="answers[<?= $questionId ?>]"
                                            value="1"
                                            class="form-check-input"
                                            id="answer-<?= $questionId ?>"
                                            <?= $answer === '1'
                                                ? 'checked'
                                                : '' ?>
                                            <?= (int) $question['required'] === 1
                                                ? 'required data-conditionally-required="1"'
                                                : '' ?>
                                        >

                                        <label
                                            class="form-check-label"
                                            for="answer-<?= $questionId ?>"
                                        >
                                            Yes
                                        </label>

                                    </div>

                                <?php endif; ?>

                            </div>

                        <?php endforeach; ?>

                    <?php endif; ?>

                </div>

                <div
                    class="card-footer d-flex justify-content-between"
                >

                    <a
                        href="/admin/events/<?= (int) $registration['event_id'] ?>/registrations"
                        class="btn btn-outline-secondary"
                    >
                        Cancel
                    </a>

                    <button
                        type="submit"
                        class="btn btn-primary"
                    >
                        Save Changes
                    </button>

                </div>

            </form>

        </div>
    </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const questionContainers =
        document.querySelectorAll('.conditional-question');

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

    function setRequiredState(
        container,
        enabled
    ) {
        const requiredFields =
            container.querySelectorAll(
                '[data-conditionally-required="1"]'
            );

        requiredFields.forEach(function (field) {
            field.required = enabled;
        });
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
                getQuestionValue(
                    controllingQuestionId
                );

            let shouldShow = false;

            if (operator === 'equals') {
                shouldShow =
                    actualValue === expectedValue;
            } else if (
                operator === 'not_equals'
            ) {
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

    document.addEventListener(
        'change',
        function (event) {
            if (
                event.target.name
                && event.target.name.startsWith(
                    'answers['
                )
            ) {
                updateConditionalQuestions();
            }
        }
    );

    updateConditionalQuestions();
});
</script>

<?php

require dirname(__DIR__) . '/partials/footer.php';

?>
